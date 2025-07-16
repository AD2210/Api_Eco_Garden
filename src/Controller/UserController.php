<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route(path: "/api", name: "app_api_")]
final class UserController extends AbstractController
{
    private $passwordHasher;
    public function __construct(UserPasswordHasherInterface $passwordHasher) {
        $this->passwordHasher = $passwordHasher;
    }

    #[Route('/user', name: 'user_create', methods:['POST'])]
    public function createUser(Request $request, EntityManagerInterface $em, SerializerInterface $serializer, ValidatorInterface $validator): JsonResponse
    {
        $user = $serializer->deserialize($request->getContent(), User::class, 'json');

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

        // On renvoie le conseil créé avec une réponse 201
        $jsonUser = $serializer->serialize($user, 'json', ['groups' => 'users']);
        return new JsonResponse($jsonUser, Response::HTTP_CREATED, [], true);
    }

    #[Route('/user/{id}', name: 'user_update', methods: ['PUT'])]
    public function updateUser(User $currentUser, Request $request, EntityManagerInterface $em, SerializerInterface $serializer, ValidatorInterface $validator): JsonResponse
    {
        //On extrait la requetes et on dérialise en une instance de User
        $updatedUser = $serializer->deserialize($request->getContent(), User::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentUser]);


        //On contrôle les erreurs du validator paramétré dans l'entité (Assert)
        $error = $validator->validate($updatedUser);
        if ($error->count() > 0) {
            return new JsonResponse($serializer->serialize($error, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        //On vérifie la présence de la maj du password et on hash. cela evite de hashé 2 fois
        if ($serializer->deserialize($request->getContent(), User::class, 'json')->getPassword() !== null){
            $plainPassword = $updatedUser->getPassword();
            $updatedUser->setPassword($this->passwordHasher->hashPassword($updatedUser, $plainPassword));
        }

        //On persist et sauvegarde en bdd le conseil mis à jour
        $em->persist($updatedUser);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/user/{id}', name: 'user_delete', methods: ['DELETE'])]
    public function deleteUser(User $user, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($user);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
