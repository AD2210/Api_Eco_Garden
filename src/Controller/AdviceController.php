<?php

namespace App\Controller;

use App\Entity\Advice;
use App\Repository\AdviceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route(path: "/api", name: "app_api_")]
final class AdviceController extends AbstractController
{
    #[Route('/conseil', name: 'advices', methods: ['GET'])]
    public function getAllAdvices(AdviceRepository $adviceRepository, SerializerInterface $serializer): JsonResponse
    {
        $month = (int)date('m'); // renvoie le mois en cours
        $advices = $adviceRepository->findBy(['month' => $month]);

        $jsonAdvices = $serializer->serialize($advices, 'json', ['groups' => 'advices']);
        return new JsonResponse($jsonAdvices, Response::HTTP_OK, [], true);
    }

    #[Route('/conseil/{month}', name: 'advice_month', methods: ['GET'])]
    public function getAdvicesByMonth(int $month, AdviceRepository $adviceRepository, SerializerInterface $serializer): JsonResponse
    {
        if (!preg_match('/^(?:[1-9]|1[0-2])$/', $month)) {
            throw new BadRequestHttpException("Le mois doit être un entier entre 1 et 12.");
        }
        $advices = $adviceRepository->findBy(['month' => $month]);

        $jsonAdvices = $serializer->serialize($advices, 'json', ['groups' => 'advices']);
        return new JsonResponse($jsonAdvices, Response::HTTP_OK, [], true);
    }

    #[Route('/conseil/{id}', name: 'advice_delete', methods: ['DELETE'])]
    public function deleteAdvice(Advice $advice, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($advice);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/conseil/{id}', name: 'advice_update', methods: ['PUT'])]
    public function updateAdvice(Advice $currentAdvice, Request $request, EntityManagerInterface $em, SerializerInterface $serializer, ValidatorInterface $validator): JsonResponse
    {
        //On extrait la requetes et on dérialise en une instance de Advice
        $updatedAdvice = $serializer->deserialize($request->getContent(), Advice::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentAdvice]);


        //On contrôle les erreurs du validator paramétré dans l'entité (Assert)
        $error = $validator->validate($updatedAdvice);
        if ($error->count() > 0) {
            return new JsonResponse($serializer->serialize($error, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }
        //On persist et sauvegarde en bdd le conseil mis à jour
        $em->persist($updatedAdvice);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/conseil', name: 'advice_create', methods: ['POST'])]
    public function postAdvice(Request $request, EntityManagerInterface $em, SerializerInterface $serializer, ValidatorInterface $validator): JsonResponse
    {
        //On extrait la requetes et on dérialise en une instance de Advice
        $advice = $serializer->deserialize($request->getContent(), Advice::class, 'json');

        //On contrôle les erreurs du validator paramétré dans l'entité (Assert)
        $error = $validator->validate($advice);
        if ($error->count() > 0) {
            return new JsonResponse($serializer->serialize($error, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        //On persist et sauvegarde en bdd
        $em->persist($advice);
        $em->flush();

        // On renvoie le conseil créé avec une réponse 201
        $jsonAdvice = $serializer->serialize($advice, 'json', ['groups' => 'advices']);
        return new JsonResponse($jsonAdvice, Response::HTTP_CREATED, [], true);
    }
}
