<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

class WheaterForecastCustom{
    /**
     * Filtre les données Brut de l'Api pour retouner une réponse filtrée et convertie
     * @param ResponseInterface $response
     * @return JsonResponse
     */
    public function makeCustomForcast(ResponseInterface $response) : JsonResponse {
        $data = $response->toArray(false);
        $statusCode = $response->getStatusCode();
        
        $rawFiltered = [
            'prévisions'=> $data['weather'][0]['description'] ?? null,
            'température' => $data['main']['temp'] ?? null,
            'humidité' => $data['main']['humidity'] ?? null,
            'pression' => $data['main']['pressure'] ?? null,
            'vitesse vents' => $this->windSpeedConverter($data['wind']['speed']),
            'direction vents' => $this->windDirectionConverter($data['wind']['deg'])
        ];

        return new JsonResponse(json_encode($rawFiltered), $statusCode, [],true );
    }

    /**
     * Converti une vitesse initiale en m/s vers une vitesse final en km/h
     * @param mixed $data
     * @return int|null
     */
    public function windSpeedConverter (?float $data) : ?int{
        if(is_null($data)){
            return null;
        }
        return (int)($data*3.6);
    }

    /**
     * Convertie une direction de vent en degré en rose des vents à 8 directions
     * @param mixed $data
     * @return string|null
     */
    public function windDirectionConverter (?int $data) : ?string{
        if(is_null($data)){
            return null;
        }
        $direction = ['N', 'NE', 'E', 'SE', 'S', 'SO', 'O', 'NO'];
        $index = (int)round($data /45) % 8; //on arrondi au secteur le plus proche et on retourne l'index du tableau de direction

        return $direction[$index];
    }
}