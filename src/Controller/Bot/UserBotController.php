<?php

namespace App\Controller\Bot;

use App\Repository\GameRepository;
use App\Service\Auth\DiscordUserManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bot/user')]
final class UserBotController extends AbstractBotController
{
    private DiscordUserManager $discordUserService;

    public function __construct(DiscordUserManager $discordUserService)
    {
        $this->discordUserService = $discordUserService;
    }

    #[Route('/roles/{discordId}', name: 'app_bot_user_roles_view', methods: ['GET'])]
    public function getRoles(string $discordId, GameRepository $gameRepository): JsonResponse
    {
        try { 
            $user = $this->discordUserService->findOrCreateUserByDiscordId($discordId);

            $roles = $user->getRoles();

            return $this->successResponse($roles);
            
        } catch (\Throwable $e) {
            return $this->errorResponse("Erreur interne du serveur.", Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}