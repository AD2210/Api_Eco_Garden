<?php

namespace App\Controller;

use App\Repository\AdviceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route(path: "/api", name: "app_api_")]
final class AdviceController extends AbstractController
{
    #[Route('/conseil', name: 'advices', methods: ['GET'])]
    public function getAllAdvices(AdviceRepository $adviceRepository, SerializerInterface $serializer): JsonResponse
    {
        $advices = $adviceRepository->findAll();

        $jsonAdvices = $serializer->serialize($advices, 'json', ['groups' => 'advices']);
        return new JsonResponse($jsonAdvices, Response::HTTP_OK, [], true);
    }

    // le requirement permet de générer une erreur 404 qu'il faudra convertir via un subscriber en erreur 400
    #[Route('/conseil/{month}', requirements: ['month' => '[1-9]|1[0-2]'], name: 'advice_month', methods: ['GET'])]
    public function getAdvicesByMonth(int $month, AdviceRepository $adviceRepository, SerializerInterface $serializer): JsonResponse
    {
        $advices = $adviceRepository->findBy(['month'=> $month]);

        $jsonAdvices = $serializer->serialize($advices, 'json', ['groups' => 'advices']);
        return new JsonResponse($jsonAdvices, Response::HTTP_OK, [], true);
    }
}
