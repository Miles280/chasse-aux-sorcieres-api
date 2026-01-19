<?php
namespace App\Controller\Bot;

use App\Entity\User;
use App\Exception\BotAuthException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bot/auth')]
class AuthController extends AbstractBotController
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
        try {
            $this->validateBotKey($request);

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

            return $this->successResponse(['token' => $token]);
        } catch (BotAuthException $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_UNAUTHORIZED);
        } catch (\Throwable $e) {
            return $this->errorResponse("Erreur interne du serveur.", Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
