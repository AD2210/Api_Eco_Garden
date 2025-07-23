<?php

namespace App\Controller;

use App\Entity\User;
use OpenApi\Attributes as OA;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route(path: "/api", name: "app_api_")]
final class UserController extends AbstractController
{
    private $passwordHasher;
    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    #[OA\RequestBody(
        description: 'body au format json',
        content: new OA\JsonContent(
            ref: new Model(type: User::class, groups: ['create'])
        )
    )]
    #[OA\Response(response: 201, description: 'Crée avec succès')]
    #[OA\Response(response: 400, description: 'Mauvaise requête, vérifier votre saisie dans le body')]
    #[OA\Tag(name: "User")]
    #[Route('/user', name: 'user_create', methods: ['POST'])]
    /**
     * Crée un nouvel utilisateur
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param SerializerInterface $serializer
     * @param ValidatorInterface $validator
     * @throws BadRequestHttpException
     * @return JsonResponse
     */
    public function createUser(
        Request $request,
        EntityManagerInterface $em,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse {
        $user = $serializer->deserialize($request->getContent(), User::class, 'json');

        // on rejete tout champ "roles", Pas Obligatoire car forcer plus bas
        $data = json_decode($request->getContent(), true);
        if (isset($data['roles'])) {
            throw new BadRequestHttpException("Le champ 'roles' n'est pas autorisé lors de la création.");
        }

        //On contrôle les erreurs du validator paramétré dans l'entité (Assert)
        $error = $validator->validate($user);
        if ($error->count() > 0) {
            return new JsonResponse($serializer->serialize($error, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        //On hash le password pour sauvegarde bdd
        $plainPassword = $user->getPassword();
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        $user->setRoles(["ROLE_USER"]); // On force ROLE_USER à la création pour eviter que n'importe qui soit Admin

        //On persist et sauvegarde en bdd
        $em->persist($user);
        $em->flush();

        // On renvoie l'utilisateur' créé avec une réponse 201
        $jsonUser = $serializer->serialize($user, 'json', ['groups' => 'users']);
        return new JsonResponse($jsonUser, Response::HTTP_CREATED, [], true);
    }

    #[OA\Parameter(
        name: 'id',
        in: 'path',
        schema: new OA\Schema(type: 'int'),
        description: 'Identifiant'
    )]
    #[OA\RequestBody(
        description: 'body au format json',
        content: new OA\JsonContent(
            ref: new Model(type: User::class, groups: ['edit'])
        )
    )]
    #[OA\Response(response: 204, description: 'Succès')]
    #[OA\Response(response: 400, description: 'Mauvaise requête, vérifier que le paramètre `id` est un nombre')]
    #[OA\Response(response: 401, description: 'Non Authentifié, renseigner votre token JWT dans `Authorize`')]
    #[OA\Response(response: 403, description: 'Non Autorisé, vous devez être administrateur pour effectué cette action')]
    #[OA\Response(response: 404, description: 'Non Trouvé, cet id n\'exite pas')]
    #[OA\Tag(name: "User")]
    #[Route('/user/{id}', name: 'user_update', methods: ['PUT'])]
    /**
     * Mise à jour d'un utilisateur
     * @param User $currentUser
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param SerializerInterface $serializer
     * @param ValidatorInterface $validator
     * @return JsonResponse
     */
    public function updateUser(
        User $currentUser,
        Request $request,
        EntityManagerInterface $em,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse {
        //On extrait la requetes et on dérialise en une instance de User
        $updatedUser = $serializer->deserialize($request->getContent(), User::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentUser]);


        //On contrôle les erreurs du validator paramétré dans l'entité (Assert)
        $error = $validator->validate($updatedUser);
        if ($error->count() > 0) {
            return new JsonResponse($serializer->serialize($error, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        //On vérifie la présence de la maj du password et on hash. Cela evite de hashé 2 fois
        if ($serializer->deserialize($request->getContent(), User::class, 'json')->getPassword() !== null) {
            $plainPassword = $updatedUser->getPassword();
            $updatedUser->setPassword($this->passwordHasher->hashPassword($updatedUser, $plainPassword));
        }

        //On persist et sauvegarde en bdd le conseil mis à jour
        $em->persist($updatedUser);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[OA\Parameter(
        name: 'id',
        in: 'path',
        schema: new OA\Schema(type: 'int'),
        description: 'Identifiant'
    )]
    #[OA\Response(response: 204, description: 'Succès')]
    #[OA\Response(response: 400, description: 'Mauvaise requête, vérifier que le paramètre `id` est un nombre')]
    #[OA\Response(response: 401, description: 'Non Authentifié, renseigner votre token JWT dans `Authorize`')]
    #[OA\Response(response: 403, description: 'Non Autorisé, vous devez être administrateur pour effectué cette action')]
    #[OA\Response(response: 404, description: 'Non Trouvé, cet id n\'exite pas')]
    #[OA\Tag(name: "User")]
    #[Route('/user/{id}', name: 'user_delete', methods: ['DELETE'])]
    /**
     * Supprime un utilisateur
     * @param User $user
     * @param int $id
     * @param EntityManagerInterface $em
     * @throws BadRequestHttpException
     * @return JsonResponse
     */
    public function deleteUser(User $user, int $id, EntityManagerInterface $em): JsonResponse
    {
        // On vérifie le pattern de saisie de l'id et on renvoie une erreur 400 si erroné
        if (!preg_match('/^[1-9][0-9]*$/', $id)) {
            throw new BadRequestHttpException("L'id doit être un nombre entier positif");
        }

        $em->remove($user);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
