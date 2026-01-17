<?php

namespace App\Controller\Bot;

use App\Service\Auth\DiscordUserManager;
use App\Service\CasinoService;
use App\Service\RequestPayloadService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
        // 1. On valide les données reçues
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

        // 2. Récupération du user
        $user = $this->discordUserService->findOrCreateUserByDiscordId($userId);
        $oldRubies = $user->getRubies();

        // 3. Appel du service "intelligent"
        $result = $this->casinoService->processCasinoTransaction($user, $amount, $operation);

        // Si le service renvoie une erreur (ex: solde insuffisant)
        if ($result && isset($result['error'])) {
            return $this->json(['error' => $result['error']], 400);
        }

        // 4. Retour de la réponse
        return $this->json([
            'success' => true,
            'old' => $oldRubies,
            'rubies' => $user->getRubies(),
            
        ]);
    }
}
