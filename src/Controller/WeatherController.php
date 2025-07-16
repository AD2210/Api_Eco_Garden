<?php

namespace App\Controller;

use App\Service\WheaterForecastCustom;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route(path: "/api", name: "app_api_")]
final class WeatherController extends AbstractController
{
    // @todo utiliser le zipcode associé au compte, necessite authentification
    #[Route('/meteo', name: 'weather')]
    public function wheatherByUserZipCode(HttpClientInterface $httpClient, WheaterForecastCustom $wheaterForecastService): JsonResponse
    {
        $zipCode = 22100;
        $baseUrl = 'https://api.openweathermap.org/data/2.5/weather?zip='
            . $zipCode
            . ',FR&appid='
            . $_ENV['WHEATHER_API_KEY']
            . '&lang=fr&units=metric';

        $response = $httpClient->request(
            'GET',
            $baseUrl
        );

        $customForecast = $wheaterForecastService->makeCustomForcast($response);

        return $customForecast;
    }

    #[Route('/meteo/{city}', name: 'weather_city')]
    public function index(string $city, HttpClientInterface $httpClient, WheaterForecastCustom $wheaterForecastService): JsonResponse
    {
        $baseUrl = 'https://api.openweathermap.org/data/2.5/weather?q='
            .$city 
            .',FR&appid='
            .$_ENV['WHEATHER_API_KEY']
            .'&lang=fr&units=metric';

        $response = $httpClient->request(
            'GET',
            $baseUrl
        );

        $customForecast = $wheaterForecastService->makeCustomForcast($response);

        return $customForecast;
    }
}
