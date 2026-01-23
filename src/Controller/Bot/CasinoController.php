<?php

namespace App\Controller\Bot;

use App\Enum\CasinoGame;
use App\Exception\EconomyException;
use App\Exception\InvalidPayloadException;
use App\Service\Auth\DiscordUserManager;
use App\Service\CasinoService;
use App\Service\RequestPayloadService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bot/casino')]
final class CasinoController extends AbstractBotController
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
        try {
            // Extraction et validation des données JSON envoyées par le bot
            $payload = $payloadService->extractValidatedPayload($request, ['discordId', 'amount', 'operation']);

            $userId = $payload['discordId'];
            $amount = (int) $payload['amount']; 
            $operation = $payload['operation']; 

            // Validation de base
            if (!in_array($operation, ['add', 'remove'])) {
                throw new EconomyException("L'opération doit être 'add' ou 'remove'.");
            }
            
            if ($amount <= 0) {
                throw new EconomyException('Le montant doit être un nombre positif.');
            }

            // Récupération de l'utilisateur et de ses données
            $user = $this->discordUserService->findOrCreateUserByDiscordId($userId);
            $oldRubies = $user->getRubies();

            // Appel du service
            $this->casinoService->processCasinoTransaction($user, $amount, $operation);

            return $this->successResponse([
                'previous' => $oldRubies,
                'current' => $user->getRubies()
            ]);
        } catch (InvalidPayloadException | EconomyException $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->errorResponse("Erreur interne du serveur.", Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/log-game', name: 'app_casino_log', methods: ['POST'])]
    public function logGame(Request $request, RequestPayloadService $payloadService): JsonResponse
    {
        try {
            // Extraction et validation des données JSON envoyées par le bot
            $payload = $payloadService->extractValidatedPayload($request, ['discordId', 'gameName', 'betAmount', 'winAmount', 'details']);

            $gameName = CasinoGame::tryFrom($payload['gameName']);
            $betAmount = $payload['betAmount'];
            $winAmount = $payload['winAmount'];
            $details = $payload['details'];
            
            // Récupération de l'utilisateur
            $user = $this->discordUserService->findOrCreateUserByDiscordId($payload['discordId']);

            if (!$gameName) {
                throw new InvalidPayloadException("Le nom du jeu '{$payload['gameName']}' est invalide.");
            }

            // Appel du service
            $this->casinoService->saveData($user, $gameName, $betAmount, $winAmount, $details);

            return $this->successResponse([]);
        } catch (InvalidPayloadException $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->errorResponse("Erreur interne du serveur.", Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
