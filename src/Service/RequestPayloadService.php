<?php

namespace App\Service;

use App\Exception\InvalidPayloadException;
use Symfony\Component\HttpFoundation\Request;

class RequestPayloadService
{
    /**
     * Extrait et valide le corps JSON d'une requête HTTP.
     * * @param Request $request
     * * @param array $requiredFields Liste des champs requis à vérifier
     * * @return array Retourne le payload si la validation réussit
     * * @throws InvalidPayloadException Si le JSON est invalide ou si un champ est manquant
     */
    public function extractValidatedPayload(Request $request, array $requiredFields): array
    {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            throw new InvalidPayloadException('Payload JSON invalide.');
        }

        foreach ($requiredFields as $field) {
            if (!isset($payload[$field])) {
                throw new InvalidPayloadException('Champ manquant : $field');
            }
        }

        return $payload;
    }
}
