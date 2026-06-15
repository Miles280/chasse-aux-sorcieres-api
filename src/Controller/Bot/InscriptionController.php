<?php

namespace App\Controller\Bot;

use App\Exception\GameException;
use App\Exception\InvalidPayloadException;
use App\Service\Auth\DiscordUserManager;
use App\Service\InscriptionService;
use App\Service\RequestPayloadService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[Route('/bot/inscription')]
final class InscriptionController extends AbstractBotController
{
    private InscriptionService $inscriptionService;
    private DiscordUserManager $discordUserService;
    private NormalizerInterface $normalizer;

    public function __construct(
        InscriptionService $inscriptionService, 
        DiscordUserManager $discordUserService,
        NormalizerInterface $normalizer
    ) {
        $this->inscriptionService = $inscriptionService;
        $this->discordUserService = $discordUserService;
        $this->normalizer = $normalizer;
    }

    #[Route('/create', name: 'app_bot_game_create', methods: ['POST'])]
    public function create(Request $request, RequestPayloadService $payloadService): JsonResponse
    {
        try {
            $payload = $payloadService->extractValidatedPayload($request, ['gameMasterId']);
            
            $gameMaster = $this->discordUserService->findOrCreateUserByDiscordId($payload['gameMasterId']);
            $game = $this->inscriptionService->createWaitingGame($gameMaster);

            // 🟢 On utilise les groupes de sérialisation
            $data = $this->normalizer->normalize($game, null, ['groups' => ['game:read', 'gameplayer:read']]);
            
            return $this->successResponse($data);

        } catch (InvalidPayloadException $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (GameException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->errorResponse("Erreur: " . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/waiting', name: 'app_bot_game_waiting_get', methods: ['GET'])]
    public function getWaiting(): JsonResponse
    {
        try {
            $game = $this->inscriptionService->getCurrentWaitingGame();

            if (!$game) {
                return $this->errorResponse("Aucune partie en attente.", Response::HTTP_NOT_FOUND);
            }

            $data = $this->normalizer->normalize($game, null, ['groups' => ['game:read', 'gameplayer:read']]);
            return $this->successResponse($data);

        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/give', name: 'app_bot_game_give', methods: ['POST'])]
    public function give(Request $request, RequestPayloadService $payloadService): JsonResponse
    {
        try {
            $payload = $payloadService->extractValidatedPayload($request, ['discordId']);
            $gameId = $this->inscriptionService->getCurrentWaitingGame()->getId();

            $game = $this->inscriptionService->giveGameMaster(
                $gameId,
                $payload['discordId']
            );

            $data = $this->normalizer->normalize($game, null, ['groups' => ['game:read', 'gameplayer:read']]);
            return $this->successResponse($data);

        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/messages/{id}', name: 'app_bot_game_messages_update', methods: ['PATCH'])]
    public function updateMessages(int $id, Request $request, RequestPayloadService $payloadService): JsonResponse
    {
        try {
            $payload = $payloadService->extractValidatedPayload($request, ['inscriptionMessageId', 'compoMessageId']);
            $this->inscriptionService->updateDiscordMessageIds($id, $payload['inscriptionMessageId'], $payload['compoMessageId']);

            return $this->successResponse(['message' => 'Messages mis à jour']);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/inscription/{id}', name: 'app_bot_game_player_inscription', methods: ['POST'])]
    public function inscriptionPlayer(int $id, Request $request, RequestPayloadService $payloadService): JsonResponse
    {
        try {
            $payload = $payloadService->extractValidatedPayload($request, ['discordId', 'action']);
            $user = $this->discordUserService->findOrCreateUserByDiscordId($payload['discordId']);

            $this->inscriptionService->inscriptionPlayerInGame($id, $user, $payload['action']);
            
            $game = $this->inscriptionService->getGameById($id);

            $data = $this->normalizer->normalize($game, null, ['groups' => ['game:read', 'gameplayer:read']]);
            return $this->successResponse($data);

        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    #[Route('/cancel/{id}', name: 'app_bot_game_cancel', methods: ['DELETE'])]
    public function cancelGame(int $id): JsonResponse
    {
        try {
            $this->inscriptionService->cancelGame($id);

            return $this->successResponse(['message' => 'Partie annulée et supprimée']);
        } catch (GameException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'app_bot_game_get', methods: ['GET'])]
    public function getById(int $id): JsonResponse
    {
        try {
            $game = $this->inscriptionService->getGameById($id);

            if (!$game) {
                throw new GameException("Partie introuvable.", Response::HTTP_NOT_FOUND);
            }

            $data = $this->normalizer->normalize($game, null, ['groups' => ['game:read', 'gameplayer:read']]);
            return $this->successResponse($data);

        } catch (GameException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}