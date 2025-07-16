<?php

namespace App\Controller;

use App\Entity\Advice;
use App\Repository\AdviceRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
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

    #[Route('/conseil', name: 'advices', methods: ['GET'])]
    public function getAllAdvices(AdviceRepository $adviceRepository, SerializerInterface $serializer): JsonResponse
    {
        $month = (int)date('m'); // renvoie le mois en cours
        $advices = $adviceRepository->findBy(['month' => $month]);

        // On affiche une réponse personnalisé car la requete est bonne mais aucune données
        if (empty($advices)){
            $advices = ['Aucun conseil publié pour le mois en cours'];
        }

        $jsonAdvices = $serializer->serialize($advices, 'json', ['groups' => 'advices']);
        return new JsonResponse($jsonAdvices, Response::HTTP_OK, [], true);
    }

    #[Route('/conseil/{month}', name: 'advice_month', methods: ['GET'])]
    public function getAdvicesByMonth(int $month, AdviceRepository $adviceRepository, SerializerInterface $serializer): JsonResponse
    {
        // On vérifie le pattern de saisie du mois et on renvoie une erreur 400 si erroné
        if (!preg_match('/^(?:[1-9]|1[0-2])$/', $month)) {
            throw new BadRequestHttpException("Le mois doit être un entier entre 1 et 12.");
        }

        $advices = $adviceRepository->findBy(['month' => $month]);

        // On affiche une réponse personnalisé car la requete est bonne mais aucune données
        if (empty($advices)){
            $advices = ['Aucun conseil publié pour le mois selectionné'];
        }

        $jsonAdvices = $serializer->serialize($advices, 'json', ['groups' => 'advices']);
        return new JsonResponse($jsonAdvices, Response::HTTP_OK, [], true);
    }

    #[Route('/conseil/{id}', name: 'advice_delete', methods: ['DELETE'])]
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

    #[Route('/conseil/{id}', name: 'advice_update', methods: ['PUT'])]
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

    //@todo 400 si non traité avec validator (exemple text non null si mal orthographié)
    #[Route('/conseil', name: 'advice_create', methods: ['POST'])]
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
