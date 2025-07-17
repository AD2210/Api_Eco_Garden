<?php

namespace App\Controller;

use App\Entity\Advice;
use OpenApi\Attributes as OA;
use App\Repository\UserRepository;
use App\Repository\AdviceRepository;
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
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[Route(path: "/api", name: "app_api_")]
final class AdviceController extends AbstractController
{
    private $jwtManager;
    private $tokenStorageInterface;
    public function __construct(TokenStorageInterface $tokenStorageInterface, JWTTokenManagerInterface $jwtManager)
    {
        $this->jwtManager = $jwtManager;
        $this->tokenStorageInterface = $tokenStorageInterface;
    }

    #[OA\Response(
        response: 200,
        description: 'Succès',
        content: new OA\JsonContent(
            ref: new Model(type: Advice::class, groups: ['advices'])
        )
    )]
    #[OA\Response(response: 401, description: 'Non Authentifié')]
    #[OA\Tag(name: "Conseil")]
    #[Security(name: 'Bearer')]
    #[Route('/conseil', name: 'advices', methods: ['GET'])]
    /**
     * Renvoi les conseils du mois en cours
     * @param AdviceRepository $adviceRepository
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    public function getAllAdvices(AdviceRepository $adviceRepository, SerializerInterface $serializer): JsonResponse
    {
        $month = (int)date('m'); // renvoie le mois en cours
        $advices = $adviceRepository->findBy(['month' => $month]);

        // On affiche une réponse personnalisé car la requete est bonne mais aucune données
        if (empty($advices)) {
            $advices = ['Aucun conseil publié pour le mois en cours'];
        }

        $jsonAdvices = $serializer->serialize($advices, 'json', ['groups' => 'advices']);
        return new JsonResponse($jsonAdvices, Response::HTTP_OK, [], true);
    }

    #[OA\Parameter(
        name: 'mois',
        in: 'path',
        schema: new OA\Schema(type: 'int'),
        description: 'Mois'
    )]
    #[OA\Response(
        response: 200,
        description: 'Succès',
        content: new OA\JsonContent(
            ref: new Model(type: Advice::class, groups: ['advices'])
        )
    )]
    #[OA\Response(response: 400, description: 'Mauvaise requête, vérifier que le paramètre `mois` est un nombre entre 1 et 12')]
    #[OA\Response(response: 401, description: 'Non Authentifié, renseigner votre token JWT dans `Authorize`')]
    #[OA\Tag(name: "Conseil")]
    #[Security(name: 'Bearer')]
    #[Route('/conseil/{mois}', name: 'advice_month', methods: ['GET'])]
    /**
     * Renvoi les conseils pour un mois donné
     * @param int $mois
     * @param AdviceRepository $adviceRepository
     * @param SerializerInterface $serializer
     * @throws BadRequestHttpException
     * @return JsonResponse
     */
    public function getAdvicesByMonth(int $mois, AdviceRepository $adviceRepository, SerializerInterface $serializer): JsonResponse
    {
        // On vérifie le pattern de saisie du mois et on renvoie une erreur 400 si erroné
        if (!preg_match('/^(?:[1-9]|1[0-2])$/', $mois)) {
            throw new BadRequestHttpException("Le mois doit être un entier entre 1 et 12.");
        }

        $advices = $adviceRepository->findBy(['month' => $mois]);

        // On affiche une réponse personnalisé car la requete est bonne mais aucune données
        if (empty($advices)) {
            $advices = ['Aucun conseil publié pour le mois selectionné'];
        }

        $jsonAdvices = $serializer->serialize($advices, 'json', ['groups' => 'advices']);
        return new JsonResponse($jsonAdvices, Response::HTTP_OK, [], true);
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
    #[OA\Tag(name: "Conseil")]
    #[Security(name: 'Bearer')]
    #[Route('/conseil/{id}', name: 'advice_delete', methods: ['DELETE'])]
    /**
     * Supprime un conseil
     * @param Advice $advice
     * @param int $id
     * @param EntityManagerInterface $em
     * @throws BadRequestHttpException
     * @return JsonResponse
     */
    public function deleteAdvice(Advice $advice, int $id, EntityManagerInterface $em): JsonResponse
    {
        // On vérifie le pattern de saisie de l'id et on renvoie une erreur 400 si erroné
        if (!preg_match('/^[1-9][0-9]*$/', $id)) {
            throw new BadRequestHttpException("L'id doit être un nombre entier positif");
        }

        $em->remove($advice);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
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
            ref: new Model(type: Advice::class, groups: ['edit'])
        )
    )]
    #[OA\Response(response: 204, description: 'Succès')]
    #[OA\Response(response: 400, description: 'Mauvaise requête, vérifier que le paramètre `id` est un nombre')]
    #[OA\Response(response: 401, description: 'Non Authentifié, renseigner votre token JWT dans `Authorize`')]
    #[OA\Response(response: 403, description: 'Non Autorisé, vous devez être administrateur pour effectué cette action')]
    #[OA\Response(response: 404, description: 'Non Trouvé, cet id n\'exite pas')]
    #[OA\Tag(name: "Conseil")]
    #[Security(name: 'Bearer')]
    #[Route('/conseil/{id}', name: 'advice_update', methods: ['PUT'])]
    /**
     * Mise à jour un conseil
     * @param Advice $currentAdvice
     * @param int $id
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param SerializerInterface $serializer
     * @param ValidatorInterface $validator
     * @param UserRepository $userRepository
     * @throws BadRequestHttpException
     * @return JsonResponse
     */
    public function updateAdvice(
        Advice $currentAdvice,
        int $id,
        Request $request,
        EntityManagerInterface $em,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        UserRepository $userRepository
    ): JsonResponse {

        // On vérifie le pattern de saisie de l'id et on renvoie une erreur 400 si erroné
        if (!preg_match('/^[1-9][0-9]*$/', $id)) {
            throw new BadRequestHttpException("L'id doit être un nombre entier positif");
        }

        //On récupère le token pour extraire le User
        $token = $this->jwtManager->decode($this->tokenStorageInterface->getToken());
        $user = $userRepository->findOneBy(['email' => $token['username']]);

        //On extrait la requetes et on dérialise en une instance de Advice
        $updatedAdvice = $serializer->deserialize($request->getContent(), Advice::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentAdvice]);

        //On contrôle les erreurs du validator paramétré dans l'entité (Assert)
        $error = $validator->validate($updatedAdvice);
        if ($error->count() > 0) {
            return new JsonResponse($serializer->serialize($error, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }
        //On enregistre le User ayant fait la maj
        //@todo On peut aussi vérifié que le User qui initie la maj est le créateur du post
        $updatedAdvice->setCreatedBy($user);

        //On persist et sauvegarde en bdd le conseil mis à jour
        $em->persist($updatedAdvice);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[OA\RequestBody(
        description: 'body au format json',
        content: new OA\JsonContent(
            ref: new Model(type: Advice::class, groups: ['edit'])
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Créé avec succès',
        content: new OA\JsonContent(
            ref: new Model(type: Advice::class, groups: ['advices'])
        )
    )]
    #[OA\Response(response: 400, description: 'Mauvaise requête, vérifier votre saisie')]
    #[OA\Response(response: 401, description: 'Non Authentifié, renseigner votre token JWT dans `Authorize`')]
    #[OA\Response(response: 403, description: 'Non Autorisé, vous devez être administrateur pour effectué cette action')]
    #[OA\Tag(name: "Conseil")]
    #[Security(name: 'Bearer')]
    #[Route('/conseil', name: 'advice_create', methods: ['POST'])]
    /**
     * Ajout d'un nouveau conseil
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param SerializerInterface $serializer
     * @param ValidatorInterface $validator
     * @param UserRepository $userRepository
     * @return JsonResponse
     */
    public function createAdvice(
        Request $request,
        EntityManagerInterface $em,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        UserRepository $userRepository
    ): JsonResponse {
        //On récupère le token pour extraire le User
        $token = $this->jwtManager->decode($this->tokenStorageInterface->getToken());
        $user = $userRepository->findOneBy(['email' => $token['username']]);

        //On extrait la requetes et on dérialise en une instance de Advice
        $advice = $serializer->deserialize($request->getContent(), Advice::class, 'json');

        //On contrôle les erreurs du validator paramétré dans l'entité (Assert)
        $error = $validator->validate($advice);
        if ($error->count() > 0) {
            return new JsonResponse($serializer->serialize($error, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        //On enregistre le User ayant créer le conseil
        $advice->setCreatedBy($user);

        //On persist et sauvegarde en bdd
        $em->persist($advice);
        $em->flush();

        // On renvoie le conseil créé avec une réponse 201
        $jsonAdvice = $serializer->serialize($advice, 'json', ['groups' => 'advices']);
        return new JsonResponse($jsonAdvice, Response::HTTP_CREATED, [], true);
    }
}
