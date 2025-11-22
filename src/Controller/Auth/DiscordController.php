<?php

namespace App\Controller\Auth;

use App\Service\Discord\DiscordOAuthService;
use App\Service\Discord\DiscordUserManager;
use App\Service\RequestPayloadService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/auth')]
class DiscordController extends AbstractController
{
    private DiscordOAuthService $discordService;
    private JWTTokenManagerInterface $jwtManager;
    private DiscordUserManager $discordUserService;

    public function __construct(DiscordOAuthService $discordService, JWTTokenManagerInterface $jwtManager, DiscordUserManager $discordUserService)
    {
        $this->discordService = $discordService;
        $this->jwtManager = $jwtManager;
        $this->discordUserService = $discordUserService;
    }

    #[Route('/discord', name: 'auth_discord', methods: ['POST'])]
    public function auth(Request $request, RequestPayloadService $payloadService): JsonResponse
    {
        // Extraction et validation des données JSON envoyées par le bot
        $payload = $payloadService->extractValidatedPayload($request, ['code']);
        if ($payload instanceof JsonResponse) return $payload;
        $code = $payload['code'] ?? null;

        // 1. Obtenir token Discord
        $tokenData = $this->discordService->getAccessToken($code);
        $accessToken = $tokenData['access_token'] ?? null;

        if (!$tokenData) {
            return $this->json(['error' => 'Token renvoyé par Discord manquant'], 400);
        }

        // 2. Récupérer info utilisateur
        $userInfo = $this->discordService->getUserInfo($accessToken);
        $discordId = $userInfo['id'] ?? null;

        if (!$discordId) {
            return $this->json(['error' => 'Invalid user info'], 400);
        }

        // 3. Créer ou récupérer utilisateur en DB
        $user = $this->discordUserService->findOrCreateUserByDiscordId($discordId);
        $this->discordUserService->updateUserFromDiscord($user, $userInfo, $tokenData);

        // 4. Générer JWT
        $jwt = $this->jwtManager->create($user);

        return $this->json(['token' => $jwt]);
    }
}
