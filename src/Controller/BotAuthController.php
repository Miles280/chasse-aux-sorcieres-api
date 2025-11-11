<?php
namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bot/auth')]
class BotAuthController extends AbstractController
{
    private $userRepo;
    private $jwtManager;
    private $em;

    public function __construct(UserRepository $userRepo, JWTTokenManagerInterface $jwtManager, EntityManagerInterface $em)
    {
        $this->userRepo = $userRepo;
        $this->jwtManager = $jwtManager;
        $this->em = $em;
    }

    #[Route('/login', name: 'app_bot_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $key = $request->headers->get('BOT-SECRET-KEY');
        if (!$key || $key !== $_ENV['BOT_SECRET_KEY']) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        // Option A: utiliser un "bot user" en base (recommandé si tu veux des permissions)
        $botDiscordId = $_ENV['BOT_DISCORD_ID'] ?? 'bot-system';
        $user = $this->userRepo->findOneBy(['discordId' => $botDiscordId]);
        if (!$user) {
            // crée un user "machine" minimal si absent
            $user = new User();
            $user->setDiscordId($botDiscordId);
            $user->setRoles(['ROLE_BOT']);

            $this->em->persist($user);
            $this->em->flush();
        }

        $token = $this->jwtManager->create($user);
        return $this->json(['token' => $token]);
    }
}
