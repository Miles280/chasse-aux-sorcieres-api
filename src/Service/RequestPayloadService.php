<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class RequestPayloadService
{
    /**
     * Extrait et valide le corps JSON d'une requête HTTP.
     * 
     * @param Request $request
     * @param array $requiredFields Liste des champs requis à vérifier
     * @return array|JsonResponse Retourne le payload ou une erreur JSON
     */
    public function extractValidatedPayload(Request $request, array $requiredFields): array|JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            return new JsonResponse(['error' => 'Payload JSON invalide.'], 400);
        }

        foreach ($requiredFields as $field) {
            if (!isset($payload[$field])) {
                return new JsonResponse(['error' => "Champ manquant : $field"], 400);
            }
        }

        return $payload;
    }
}
