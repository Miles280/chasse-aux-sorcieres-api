<?php

namespace App\Controller\Auth;

use App\Service\Auth\DiscordOAuthService;
use App\Service\Auth\DiscordUserManager;
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
    private DiscordUserManager $discordUserManager;

    public function __construct(DiscordOAuthService $discordService, JWTTokenManagerInterface $jwtManager, DiscordUserManager $discordUserManager)
    {
        $this->discordService = $discordService;
        $this->jwtManager = $jwtManager;
        $this->discordUserManager = $discordUserManager;
    }

    #[Route('/login', name: 'app_auth_login', methods: ['POST'])]
    public function login(Request $request, RequestPayloadService $payloadService): JsonResponse
    {
        // Extraction et validation des données JSON envoyées par le bot
        $payload = $payloadService->extractValidatedPayload($request, ['code']);
        if ($payload instanceof JsonResponse) return $payload;
        $code = $payload['code'];

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
        $user = $this->discordUserManager->findOrCreateUserByDiscordId($discordId);
        $this->discordUserManager->updateUserFromDiscord($user, $userInfo, $tokenData);

        $jwtRefreshToken = bin2hex(random_bytes(64));
        $this->discordUserManager->updateJwtTokens($user, $jwtRefreshToken);

        // 4. Générer JWT
        $jwt = $this->jwtManager->create($user);

        return $this->json([
            'token' => $jwt,
            'refreshToken' => $jwtRefreshToken,
        ]);
    }

    #[Route('/refresh', name: 'app_auth_refresh', methods: ['POST'])]
    public function refresh(Request $request, RequestPayloadService $payloadService): JsonResponse
    {
        $payload = $payloadService->extractValidatedPayload($request, ['refreshToken']);
        if ($payload instanceof JsonResponse) return $payload;
        $refreshToken = $payload['refreshToken'];

        $user = $this->discordUserManager->findUserByJwtRefreshToken($refreshToken);

        $newJwt = $this->jwtManager->create($user);
        $jwtRefreshToken = bin2hex(random_bytes(64));

        return $this->json([
            'token' => $newJwt,
            'refreshToken' => $jwtRefreshToken,
        ]);
    }
}
