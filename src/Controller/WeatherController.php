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
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;


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

    #[OA\Response(
        response: 200,
        description: 'Météo',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'prévisions', type: 'string', example: 'nuageux', description: 'Météo prévu'),
                new OA\Property(property: 'température', type: 'float', example: 18.5, description: 'Température en °C'),
                new OA\Property(property: 'humidité', type: 'integer', example: 77, description: 'Humidité relative en %HR'),
                new OA\Property(property: 'pression', type: 'integer', example: 1012, description: 'Pression atmosphérique en hPa'),
                new OA\Property(property: 'vitesse vents', type: 'float', example: 15.0, description: 'Vitesse des vents en km/h'),
                new OA\Property(property: 'direction vents', type: 'string', example: 'NE', description: 'Direction des vents en direction cardinale'),
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Mauvaise requête, le code postal est manquant dans votre profil')]
    #[OA\Response(response: 401, description: 'Non Authentifié, renseigner votre token JWT dans `Authorize`')]
    #[OA\Response(response: 500, description: 'Echec de l\'appel à l\'api météo')]
    #[OA\Tag(name: "Météo")]
    #[Security(name: 'Bearer')]
    #[Route('/meteo', name: 'weather', methods:['GET'])]
    /**
     * Renvoi un rapport météo grâce au code postal fourni par l'utilisateur lors de l'inscription
     * @param UserRepository $userRepository
     * @param HttpClientInterface $httpClient
     * @param WheaterForecastCustom $wheaterForecastService
     * @throws BadRequestException
     * @return JsonResponse
     */
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

    #[OA\Response(
        response: 200,
        description: 'Météo',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'prévisions', type: 'string', example: 'nuageux', description: 'Météo prévu'),
                new OA\Property(property: 'température', type: 'float', example: 18.5, description: 'Température en °C'),
                new OA\Property(property: 'humidité', type: 'integer', example: 77, description: 'Humidité relative en %HR'),
                new OA\Property(property: 'pression', type: 'integer', example: 1012, description: 'Pression atmosphérique en hPa'),
                new OA\Property(property: 'vitesse vents', type: 'float', example: 15.0, description: 'Vitesse des vents en km/h'),
                new OA\Property(property: 'direction vents', type: 'string', example: 'NE', description: 'Direction des vents en direction cardinale'),
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Mauvaise requête, le code postal est manquant dans votre profil')]
    #[OA\Response(response: 401, description: 'Non Authentifié, renseigner votre token JWT dans `Authorize`')]
    #[OA\Response(response: 500, description: 'Echec de l\'appel à l\'api météo')]
    #[OA\Tag(name: "Météo")]
    #[Security(name: 'Bearer')]
    #[Route('/meteo/{ville}', name: 'weather_city', methods:['GET'])]
    /**
     * Renvoi un rapport météo pour une ville donnée 
     * @param string $ville
     * @param HttpClientInterface $httpClient
     * @param WheaterForecastCustom $wheaterForecastService
     * @return JsonResponse
     */
    public function index(string $ville, HttpClientInterface $httpClient, WheaterForecastCustom $wheaterForecastService): JsonResponse
    {
        $baseUrl = 'https://api.openweathermap.org/data/2.5/weather?q='
            . $ville
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
