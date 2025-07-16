<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\WheaterForecastCustom;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[Route(path: "/api", name: "app_api_")]
final class WeatherController extends AbstractController
{
    private $jwtManager;
    private $tokenStorageInterface;
    public function __construct(TokenStorageInterface $tokenStorageInterface, JWTTokenManagerInterface $jwtManager)
    {
        $this->jwtManager = $jwtManager;
        $this->tokenStorageInterface = $tokenStorageInterface;
    }

    #[Route('/meteo', name: 'weather', methods:['GET'])]
    public function wheatherByUserZipCode(UserRepository $userRepository, HttpClientInterface $httpClient, WheaterForecastCustom $wheaterForecastService): JsonResponse
    {
        //On récupère le token pour extraire le User
        $token = $this->jwtManager->decode($this->tokenStorageInterface->getToken());
        $user = $userRepository->findOneBy(['email' => $token['username']]);

        $zipCode = $user->getPostalCode() ?: '';
        if ($zipCode == '') {
            throw new BadRequestException("Code postal non défini dans le user", 400);
        }

        $baseUrl = 'https://api.openweathermap.org/data/2.5/weather?zip='
            . $zipCode
            . ',FR&appid='
            . $_ENV['WHEATHER_API_KEY']
            . '&lang=fr&units=metric';

        $response = $httpClient->request(
            'GET',
            $baseUrl
        );

        return $wheaterForecastService->makeCustomForcast($response);
    }

    #[Route('/meteo/{city}', name: 'weather_city', methods:['GET'])]
    public function index(string $city, HttpClientInterface $httpClient, WheaterForecastCustom $wheaterForecastService): JsonResponse
    {
        $baseUrl = 'https://api.openweathermap.org/data/2.5/weather?q='
            . $city
            . ',FR&appid='
            . $_ENV['WHEATHER_API_KEY']
            . '&lang=fr&units=metric';

        $response = $httpClient->request(
            'GET',
            $baseUrl
        );

        return $wheaterForecastService->makeCustomForcast($response);
    }
}
