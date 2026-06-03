<?php

namespace App\Controller\Bot;

use App\Repository\GameRepository;
use App\Service\GameService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bot/game')]
final class GameController extends AbstractBotController
{
    public function __construct(
        private GameService $gameService
    ) {}

    #[Route('/{id}', name: 'app_bot_game_view', methods: ['GET'])]
    public function viewGame(int $id, GameRepository $gameRepository): JsonResponse
    {
        try {
            // On cherche la partie. Si elle n'existe pas, on le signale.
            $game = $gameRepository->find($id);
            if (!$game) {
                return $this->errorResponse("La partie $id n'existe pas.", Response::HTTP_NOT_FOUND);
            }
            // On récupère toutes les infos pertinentes pour le bot 
            // On les envoies
            return $this->successResponse([
                'infopertinente' => 'oui'
            ]);
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
}