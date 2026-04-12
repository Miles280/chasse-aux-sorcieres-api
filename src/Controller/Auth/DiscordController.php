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
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/auth')]
class DiscordController extends AbstractController
{
    // 🔥 NOUVEAU : Constantes pour la configuration du cookie
    private const REFRESH_TOKEN_COOKIE_NAME = 'refreshToken';
    private const REFRESH_TOKEN_LIFETIME = 30 * 24 * 60 * 60; // 30 jours
    private const COOKIE_PATH = '/';

    private DiscordOAuthService $discordService;
    private JWTTokenManagerInterface $jwtManager;
    private DiscordUserManager $discordUserManager;

    public function __construct(
        DiscordOAuthService $discordService, 
        JWTTokenManagerInterface $jwtManager, 
        DiscordUserManager $discordUserManager
    ) {
        $this->discordService = $discordService;
        $this->jwtManager = $jwtManager;
        $this->discordUserManager = $discordUserManager;
    }

    #[Route('/login', name: 'app_auth_login', methods: ['POST'])]
    public function login(Request $request, RequestPayloadService $payloadService): JsonResponse
    {
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
            'user' => [
                'username' => $user->getDiscordUsername(),
                'roles' => $user->getRoles()
            ]
        ]);

        $this->setRefreshTokenCookie($response, $jwtRefreshToken, $request);

        return $response;
    }

    #[Route('/refresh', name: 'app_auth_refresh', methods: ['POST'])]
    #[IsGranted('PUBLIC_ACCESS')]
    public function refresh(Request $request): JsonResponse
    {
        // 🔥 AMÉLIORÉ : Debug pour identifier le problème
        $refreshToken = $request->cookies->get(self::REFRESH_TOKEN_COOKIE_NAME);

        if (!$refreshToken) {
            return $this->json(['error' => 'Navigateur n\'a pas envoyé le cookie'], 401);
        }

        $user = $this->discordUserManager->findUserByJwtRefreshToken($refreshToken);

        if (!$user) {
            return $this->json(['error' => 'Token reçu mais non trouvé en BDD'], 401);
        }

        if ($user->getJwtRefreshTokenExpiresAt() < new \DateTime()) {
            return $this->json([
                'error' => 'Token trouvé mais expiré en BDD',
                'expires_at' => $user->getJwtRefreshTokenExpiresAt()->format('Y-m-d H:i:s')
            ], 401);
        }

        // Debug : Lister tous les cookies reçus
        $allCookies = $request->cookies->all();
        
        if (!$refreshToken) {
            return $this->json([
                'error' => 'Cookie refresh token absent',
                'debug' => [
                    'cookies_recus' => array_keys($allCookies),
                    'headers' => [
                        'origin' => $request->headers->get('Origin'),
                        'referer' => $request->headers->get('Referer'),
                    ]
                ]
            ], 401);
        }

        $user = $this->discordUserManager->findUserByJwtRefreshToken($refreshToken);

        if (!$user) {
            return $this->json([
                'error' => 'Token inconnu en DB',
                'debug' => 'Le refresh token ne correspond à aucun utilisateur'
            ], 401);
        }

        if ($user->getJwtRefreshTokenExpiresAt() < new \DateTime()) {
            return $this->json([
                'error' => 'Token expiré',
                'debug' => [
                    'expire_le' => $user->getJwtRefreshTokenExpiresAt()->format('Y-m-d H:i:s'),
                    'maintenant' => (new \DateTime())->format('Y-m-d H:i:s'),
                ]
            ], 401);
        }

        // Rotation des tokens
        $newJwt = $this->jwtManager->create($user);
        $newRefreshToken = bin2hex(random_bytes(64));
        
        $this->discordUserManager->updateJwtTokens($user, $newRefreshToken);

        $response = new JsonResponse([
            'token' => $newJwt
        ]);

        $this->setRefreshTokenCookie($response, $newRefreshToken, $request);

        return $response;
    }

    #[Route('/logout', name: 'app_auth_logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $response = $this->json(['message' => 'Logged out successfully']);
        
        // 🔥 CORRIGÉ : Utiliser les mêmes paramètres que pour la création du cookie
        $this->clearRefreshTokenCookie($response, $request);

        return $response;
    }

    /**
     * 🔥 AMÉLIORÉ : Configuration centralisée du cookie avec détection automatique du contexte
     */
    private function setRefreshTokenCookie(JsonResponse $response, string $token, Request $request): void
    {
        $isSecure = $request->isSecure();
        
        // 🔥 IMPORTANT : En développement local, si le frontend et backend sont sur des ports différents,
        // il faut utiliser SameSite=None avec Secure=true (même en HTTP localhost, Chrome l'accepte)
        $sameSite = Cookie::SAMESITE_LAX;
        
        // Si on détecte un environnement cross-origin (différents ports/domaines)
        $origin = $request->headers->get('Origin');
        if ($origin && $origin !== $request->getSchemeAndHttpHost()) {
            $sameSite = Cookie::SAMESITE_NONE;
            // SameSite=None nécessite Secure=true
            $isSecure = true;
            // Note : En dev local, certains navigateurs acceptent SameSite=None sans Secure
        }

        $cookie = Cookie::create(
            self::REFRESH_TOKEN_COOKIE_NAME,
            $token,
            time() + self::REFRESH_TOKEN_LIFETIME,
            self::COOKIE_PATH,
            null, // Domain = null = domaine courant
            $isSecure, // Secure
            true, // HttpOnly (OBLIGATOIRE pour la sécurité)
            false, // Raw
            $sameSite
        );

        $response->headers->setCookie($cookie);
    }

    /**
     * 🔥 NOUVEAU : Suppression correcte du cookie avec les mêmes paramètres
     */
    private function clearRefreshTokenCookie(JsonResponse $response, Request $request): void
    {
        $isSecure = $request->isSecure();
        $sameSite = Cookie::SAMESITE_LAX;
        
        $origin = $request->headers->get('Origin');
        if ($origin && $origin !== $request->getSchemeAndHttpHost()) {
            $sameSite = Cookie::SAMESITE_NONE;
            $isSecure = true;
        }

        // 🔥 IMPORTANT : Pour supprimer un cookie, il faut recréer le même cookie avec une date passée
        $cookie = Cookie::create(
            self::REFRESH_TOKEN_COOKIE_NAME,
            '', // Valeur vide
            time() - 3600, // Date dans le passé
            self::COOKIE_PATH,
            null,
            $isSecure,
            true,
            false,
            $sameSite
        );

        $response->headers->setCookie($cookie);
    }
}