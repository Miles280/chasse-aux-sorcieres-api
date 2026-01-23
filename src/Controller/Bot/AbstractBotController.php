<?php

namespace App\Controller\Bot;

use App\Exception\BotAuthException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractBotController extends AbstractController
{   
    /**
     * Vérifie la clé secrète du bot.
     * @throws BotAuthException Si la clé est manquante ou incorrecte
     */
    protected function validateBotKey(Request $request): void
    {
        $key = $request->headers->get('BOT-SECRET-KEY');
        $expectedKey = $_ENV['BOT_SECRET_KEY'] ?? null;

        if (!$key || $key !== $expectedKey) {
            // On utilise un code 401 (Unauthorized)
            throw new BotAuthException('Accès non autorisé : Secret Key invalide.', Response::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * Retourne une réponse d'erreur formatée pour le Bot
     */
    protected function errorResponse(string $message, int $status = 400): JsonResponse
    {
        return $this->json([
            'success' => false,
            'error'   => $message
        ], $status);
    }

    /**
     * Retourne une réponse de succès formatée pour le Bot
     */
    protected function successResponse(mixed $data = null, int $status = 200): JsonResponse
    {
        return $this->json([
            'success' => true,
            'data'    => $data
        ], $status);
    }
}