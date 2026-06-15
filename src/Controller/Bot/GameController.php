<?php

namespace App\Controller\Bot;

use App\Repository\GameRepository;
use App\Service\GameService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[Route('/bot/game')]
final class GameController extends AbstractBotController
{
    public function __construct(
        private GameService $gameService
    ) {}

    #[Route('/{id}', name: 'app_bot_game_view', methods: ['GET'])]
    public function viewGame(int $id, GameRepository $gameRepository, NormalizerInterface $normalizer): JsonResponse
    {
        try {
            $game = $gameRepository->find($id);
            
            if (!$game) {
                return $this->errorResponse("La partie $id n'existe pas.", Response::HTTP_NOT_FOUND);
            }

            // Le normalizer transforme l'entité en tableau PHP en respectant tes groupes
            $gameData = $normalizer->normalize($game, null, [
                'groups' => ['game:read']
            ]);

            return $this->successResponse($gameData);
            
        } catch (\Throwable $e) {
            return $this->errorResponse("Erreur lors de la récupération : " . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/preview/{id}', name: 'app_bot_game_preview', methods: ['POST'])]
    public function previewDistribution(int $id, GameRepository $gameRepository): JsonResponse
    {
        try {          
            $game = $gameRepository->find($id);

            if (!$game) {
                return $this->errorResponse("La partie $id n'existe pas.", Response::HTTP_NOT_FOUND);
            }

            // Génère une distribution temporaire sans la sauvegarder
            $distribution = $this->gameService->generateRandomDistribution($game);

            return $this->successResponse([
                'gameId' => $game->getId(),
                'distribution' => $distribution
            ]);
            
        } catch (\LogicException $e) {
            // Permet de catcher proprement l'erreur de nombre de joueurs vs rôles
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->errorResponse("Erreur lors de la prévisualisation : " . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/start/{id}', name: 'app_bot_game_start', methods: ['POST'])]
    public function start(int $id, Request $request, GameRepository $gameRepository): JsonResponse
    {
        try {          
            $game = $gameRepository->find($id);

            if (!$game) {
                return $this->errorResponse("La partie $id n'existe pas.", Response::HTTP_NOT_FOUND);
            }

            // On récupère le payload envoyé par le bot (la distribution validée)
            $payload = json_decode($request->getContent(), true);
            $validatedDistribution = $payload['distribution'] ?? [];

            // On lance la partie avec cette distribution
            $result = $this->gameService->startGame($game, $validatedDistribution);

            return $this->successResponse($result);
            
        } catch (\LogicException $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->errorResponse("Erreur lors du lancement : " . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/channels', name: 'app_bot_game_update_channels', methods: ['PATCH'])]
    public function updateChannels(
        int $id, 
        Request $request, 
        GameRepository $gameRepository
    ): JsonResponse {
        try {
            $game = $gameRepository->find($id);

            if (!$game) {
                return $this->errorResponse("La partie $id n'existe pas.", Response::HTTP_NOT_FOUND);
            }

            // Récupération des données brutes
            $payload = json_decode($request->getContent(), true);
            $gameChannels = $payload['gameChannels'] ?? [];
            $playersChannels = $payload['playersChannels'] ?? [];

            // Délégation de toute la logique complexe au service
            $this->gameService->updateGameChannels($game, $gameChannels, $playersChannels);

            return $this->successResponse([
                'message' => 'Salons enregistrés avec succès.',
                'gameChannels' => $game->getDiscordChannels()
            ]);

        } catch (\Throwable $e) {
            return $this->errorResponse("Erreur lors de la mise à jour des salons : " . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/trackersmessages', name: 'app_bot_game_update_trackers', methods: ['PATCH'])]
    public function updateTrackers(
        int $id, 
        Request $request, 
        GameRepository $gameRepository
    ): JsonResponse {
        try {
            $game = $gameRepository->find($id);

            if (!$game) {
                return $this->errorResponse("La partie $id n'existe pas.", Response::HTTP_NOT_FOUND);
            }

            // Récupération des données brutes
            $payload = json_decode($request->getContent(), true);
            $publicTrackerId = $payload['publicTrackerMessageId'] ?? null;
            $mjTrackerId = $payload['mjTrackerMessageId'] ?? null;

            // Délégation au service
            $this->gameService->updateGameTrackers($game, $publicTrackerId, $mjTrackerId);

            return $this->successResponse([
                'message' => 'Messages de suivi enregistrés avec succès.',
                'publicTracker' => $game->getPublicTrackerMessageId(),
                'mjTracker' => $game->getMjTrackerMessageId()
            ]);

        } catch (\Throwable $e) {
            return $this->errorResponse("Erreur lors de la mise à jour des trackers : " . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}