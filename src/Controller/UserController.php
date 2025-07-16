<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route(path: "/api", name: "app_api_")]
final class UserController extends AbstractController
{
    #[Route('/user', name: 'user_create', methods:['POST'])]
    public function createUser(Request $request, EntityManagerInterface $em, SerializerInterface $serializer, ValidatorInterface $validator): JsonResponse
    {
        //On extrait la requetes et on dérialise en une instance de User (role = [] => ROLE_USER dans le getter)
        $user = $serializer->deserialize($request->getContent(), User::class, 'json');

        //On contrôle les erreurs du validator paramétré dans l'entité (Assert)
        $error = $validator->validate($user);
        if ($error->count() > 0) {
            return new JsonResponse($serializer->serialize($error, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        //On persist et sauvegarde en bdd
        $em->persist($user);
        $em->flush();

        // On renvoie le conseil créé avec une réponse 201
        $jsonUser = $serializer->serialize($user, 'json', ['groups' => 'users']);
        return new JsonResponse($jsonUser, Response::HTTP_CREATED, [], true);
    }
}
