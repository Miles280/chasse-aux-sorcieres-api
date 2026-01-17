<?php

namespace App\Controller\Bot;

use App\Enum\CasinoGame;
use App\Service\Auth\DiscordUserManager;
use App\Service\CasinoService;
use App\Service\RequestPayloadService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bot/casino')]
final class CasinoController extends AbstractController
{
    private CasinoService $casinoService;
    private DiscordUserManager $discordUserService;

    public function __construct(CasinoService $casinoService, DiscordUserManager $discordUserService)
    {
        $this->casinoService = $casinoService;
        $this->discordUserService = $discordUserService;
    }

    #[Route('/transaction', name: 'app_bot_casino_transaction', methods: ['POST'])]
    public function casino(Request $request, RequestPayloadService $payloadService): JsonResponse
    {
        // Extraction et validation des données JSON envoyées par le bot
        $payload = $payloadService->extractValidatedPayload($request, ['discordId', 'amount', 'operation']);
        if ($payload instanceof JsonResponse) return $payload;

        $userId = $payload['discordId'];
        $amount = (int) $payload['amount']; 
        $operation = $payload['operation']; // 'add' (gain) ou 'remove' (perte)

        // Validation de base
        if (!in_array($operation, ['add', 'remove'])) {
            return $this->json(['error' => "L'opération doit être 'add' ou 'remove'."], 400);
        }
        
        if (!is_numeric($amount) || $amount <= 0) {
            return new JsonResponse(['error' => 'Le montant doit être un nombre positif.'], 400);
        }

        // Récupération de l'utilisateur et de ses données
        $user = $this->discordUserService->findOrCreateUserByDiscordId($userId);
        $oldRubies = $user->getRubies();

        // Appel du service
        $result = $this->casinoService->processCasinoTransaction($user, $amount, $operation);

        if ($result && isset($result['error'])) {
            return $this->json(['error' => $result['error']], 400);
        }

        return $this->json([
            'success' => true,
            'old' => $oldRubies,
            'rubies' => $user->getRubies(),
            
        ]);
    }

    #[Route('/log-game', name: 'app_casino_log', methods: ['POST'])]
    public function logGame(Request $request, RequestPayloadService $payloadService): JsonResponse
    {
        // Extraction et validation des données JSON envoyées par le bot
        $payload = $payloadService->extractValidatedPayload($request, ['discordId', 'game', 'betAmount', 'winAmount', 'details']);
        if ($payload instanceof JsonResponse) return $payload;

        $game = CasinoGame::tryFrom($payload['game']);
        $betAmount = $payload['betAmount'];
        $winAmount = $payload['winAmount'];
        $details = $payload['details'];
        
        // Récupération de l'utilisateur
        $user = $this->discordUserService->findOrCreateUserByDiscordId($payload['discordId']);

        if (!$game) {
            return $this->json(null, Response::HTTP_BAD_REQUEST);
        }

        // Appel du service
        $this->casinoService->saveData($user, $game, $betAmount, $winAmount, $details);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
