<?php

namespace App\Controller\Auth;

use App\Entity\User;
use App\Service\DiscordService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class DiscordController extends AbstractController
{
    private DiscordService $discordService;
    private EntityManagerInterface $em;
    private JWTTokenManagerInterface $jwtManager;

    public function __construct(DiscordService $discordService, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager)
    {
        $this->discordService = $discordService;
        $this->em = $em;
        $this->jwtManager = $jwtManager;
    }

    #[Route('/api/auth/discord', name: 'auth_discord', methods: ['POST'])]
    public function auth(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $code = $data['code'] ?? null;

        if (!$code) {
            return $this->json(['error' => 'Code missing'], 400);
        }

        // 1. Obtenir token Discord
        $tokenData = $this->discordService->getAccessToken($code);
        $accessToken = $tokenData['access_token'] ?? null;

        if (!$accessToken) {
            return $this->json(['error' => 'Invalid access token'], 400);
        }

        // 2. Récupérer info utilisateur
        $userInfo = $this->discordService->getUserInfo($accessToken);
        return $this->json($userInfo);
        $discordId = $userInfo['id'] ?? null;

        if (!$discordId) {
            return $this->json(['error' => 'Invalid user info'], 400);
        }

        // 3. Créer ou récupérer utilisateur en DB
        $user = $this->em->getRepository(User::class)->findOneBy(['discordId' => $discordId]);
        if (!$user) {
            $user = new User();
            $user->setDiscordId($discordId);
            $user->setDiscordUsername($userInfo['username'] ?? 'DiscordUser');
            $user->setEmail($userInfo['email'] ?? null);
            $this->em->persist($user);
            $this->em->flush();
        }

        // 4. Générer JWT
        $jwt = $this->jwtManager->create($user);

        return $this->json(['token' => $jwt]);
    }
}
