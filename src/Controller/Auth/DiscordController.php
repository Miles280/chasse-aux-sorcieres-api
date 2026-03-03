<?php

namespace App\Controller\Auth;

use App\Service\Auth\DiscordOAuthService;
use App\Service\Auth\DiscordUserManager;
use App\Service\RequestPayloadService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
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

        // Génération du refresh token (stocké en base)
        $jwtRefreshToken = bin2hex(random_bytes(64));
        $this->discordUserManager->updateJwtTokens($user, $jwtRefreshToken);

        // 4. Générer JWT (Access Token)
        $jwt = $this->jwtManager->create($user);

        // 5. Création de la réponse avec le Cookie HttpOnly
        $response = new JsonResponse([
            'token' => $jwt,
            // On NE renvoie PLUS le refreshToken dans le JSON
            'user' => [ // Optionnel : renvoyer quelques infos utiles tout de suite
                'username' => $user->getDiscordUsername(),
                'roles' => $user->getRoles()
            ]
        ]);

        $this->setRefreshTokenCookie($response, $jwtRefreshToken, $request->isSecure());

        return $response;
    }

    #[Route('/refresh', name: 'app_auth_refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        // On récupère le refresh token directement depuis le COOKIE, pas le body
        $refreshToken = $request->cookies->get('refreshToken');

        if (!$refreshToken) {
            // Cas 1 : Le navigateur n'a pas envoyé le cookie
            return $this->json(['debug' => 'Cookie absent'], 401); 
        }

        $user = $this->discordUserManager->findUserByJwtRefreshToken($refreshToken);

        if (!$user) {
            // Cas 2 : Le token dans le cookie ne correspond à rien en Base de Données
            return $this->json(['debug' => 'Token inconnu en DB', 'token_recu' => $refreshToken], 401);
        }

        if ($user->getJwtRefreshTokenExpiresAt() < new \DateTime()) {
            // Cas 3 : Le token est trouvé, mais la date d'expiration en DB est passée
            return $this->json(['debug' => 'Token expiré en DB'], 401);
        }

        // Rotation des tokens
        $newJwt = $this->jwtManager->create($user);
        $newRefreshToken = bin2hex(random_bytes(64));
        
        // Mise à jour en base
        $this->discordUserManager->updateJwtTokens($user, $newRefreshToken);

        $response = new JsonResponse([
            'token' => $newJwt
        ]);

        // Mise à jour du cookie avec le nouveau token
        $this->setRefreshTokenCookie($response, $newRefreshToken, $request->isSecure());

        return $response;
    }

    #[Route('/logout', name: 'app_auth_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        $response = $this->json(['message' => 'Logged out successfully']);
        
        // On demande au navigateur de supprimer le cookie
        $response->headers->clearCookie('refreshToken', '/', null, true, true, 'Lax'); // Assure-toi que les params matchent ceux de la création

        return $response;
    }

    /**
     * Méthode privée pour configurer le cookie de manière centralisée
     */
    private function setRefreshTokenCookie(JsonResponse $response, string $token, bool $isSecure): void
    {
        $cookie = Cookie::create(
            'refreshToken',       // Nom
            $token,               // Valeur
            time() + (30 * 24 * 60 * 60), // Expiration (30 jours)
            '/',                  // Path
            null,                 // Domain (null = domaine courant)
            $isSecure,            // Secure (True en prod HTTPS, False en dev HTTP)
            true,                 // HttpOnly (C'est LA clé de la sécurité)
            false,                // Raw
            Cookie::SAMESITE_LAX  // SameSite (Lax est un bon compromis sécurité/ux)
        );

        $response->headers->setCookie($cookie);
    }
}