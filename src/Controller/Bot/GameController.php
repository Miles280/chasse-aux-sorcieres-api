<?php

namespace App\Controller\Bot;

use App\Enum\GameStatus;
use App\Enum\GameStep;
use App\Repository\GameRepository;
use App\Service\GameService;
use App\Service\RequestPayloadService;
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
    
    #[Route('/active', name: 'app_bot_game_active', methods: ['GET'])]
    public function getActiveGame(GameRepository $gameRepository, NormalizerInterface $normalizer): JsonResponse
    {
        try {
            // On cherche la partie dont le statut est PLAYING
            $game = $gameRepository->findOneBy(['status' => GameStatus::PLAYING]);
            
            if (!$game) {
                return $this->errorResponse("Aucune partie n'est actuellement en cours.", Response::HTTP_NOT_FOUND);
            }

            $gameData = $normalizer->normalize($game, null, [
                'groups' => ['game:read']
            ]);

            return $this->successResponse($gameData);
            
        } catch (\Throwable $e) {
            return $this->errorResponse("Erreur lors de la récupération de la partie active : " . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

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
        int $id, Request $request, GameRepository $gameRepository, RequestPayloadService $payloadService): JsonResponse {
        try {
            $game = $gameRepository->find($id);

            if (!$game) {
                return $this->errorResponse("La partie $id n'existe pas.", Response::HTTP_NOT_FOUND);
            }

            // Récupération des données brutes
            $payload = $payloadService->extractValidatedPayload($request, ['gameChannels', 'playersChannels']);
            $gameChannels = $payload['gameChannels'];
            $playersChannels = $payload['playersChannels'];

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
    public function updateTrackers( int $id, Request $request, GameRepository $gameRepository, RequestPayloadService $payloadService): JsonResponse {
        try {
            $game = $gameRepository->find($id);

            if (!$game) {
                return $this->errorResponse("La partie $id n'existe pas.", Response::HTTP_NOT_FOUND);
            }

            // Récupération des données brutes
            $payload = $payloadService->extractValidatedPayload($request, ['publicTrackerMessageId', 'mjTrackerMessageId']);
            $publicTrackerId = $payload['publicTrackerMessageId'];
            $mjTrackerId = $payload['mjTrackerMessageId'];

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

    #[Route('/{id}/step', name: 'app_bot_game_update_step', methods: ['PATCH'])]
    public function updateStep(int $id, Request $request, GameRepository $gameRepository, NormalizerInterface $normalizer, RequestPayloadService $payloadService): JsonResponse {
        try {
            $payload = $payloadService->extractValidatedPayload($request, ['step']);
            $nextStep = $payload['step'];

            $game = $gameRepository->find($id);

            if (!$game) {
                return $this->errorResponse("La partie $id n'existe pas.", Response::HTTP_NOT_FOUND);
            }

            // Exécution du changement de phase dans le service
            $this->gameService->changeGameStep($game, $nextStep);

            // On normalise la game mise à jour pour la renvoyer directement au bot
            $gameData = $normalizer->normalize($game, null, [
                'groups' => ['game:read']
            ]);

            return $this->successResponse($gameData);

        } catch (\InvalidArgumentException $e) {
            // Capturera l'erreur si la phase envoyée n'existe pas dans l'Enum
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->errorResponse("Erreur lors du changement de phase : " . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/kill', name: 'app_bot_game_kill', methods: ['POST'])]
    public function killPlayer(int $id, Request $request, GameRepository $gameRepository, NormalizerInterface $normalizer,RequestPayloadService $payloadService): JsonResponse {
        try {
           
            $game = $gameRepository->find($id);

            if (!$game) {
                return $this->errorResponse("La partie $id n'existe pas.", Response::HTTP_NOT_FOUND);
            }
            
            // On s'attend à recevoir : pseudo (ou discordId), reason, hideRole, fakeRoleId
            $payload = $payloadService->extractValidatedPayload($request, ['discordId', 'deathCause', 'hideRole', 'fakeRoleId']);
            $discordId = $payload['discordId']; 
            $deathCauseValue = $payload['deathCause'];
            $hideRole = $payload['hideRole'];
            $fakeRoleId = $payload['fakeRoleId'];

            // Délégation au GameService pour la logique métier
            $this->gameService->killPlayer($game, $discordId, $deathCauseValue, $hideRole, $fakeRoleId);

            // On normalise le log et le joueur pour le retour bot
            $gameData = $normalizer->normalize($game, null, [
                'groups' => ['game:read']
            ]);

            return $this->successResponse($gameData);

        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->errorResponse("Erreur lors de l'enregistrement du kill : " . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/night-deaths', name: 'app_bot_game_night_deaths', methods: ['GET'])]
    public function getNightDeaths(int $id, GameRepository $gameRepository, NormalizerInterface $normalizer): JsonResponse
    {
        try {
            $game = $gameRepository->find($id);

            if (!$game) {
                return $this->errorResponse("La partie $id n'existe pas.", Response::HTTP_NOT_FOUND);
            }

            $deadPlayers = [];
            $currentDay = $game->getDayNumber();

            foreach ($game->getGameLogs() as $log) {
                // On vérifie que la mort a eu lieu ce jour-ci, pendant la nuit ou l'aube
                if ($log->getDayNumber() === $currentDay && in_array($log->getStep(), [GameStep::NIGHT, GameStep::DAWN])) {
                    $player = $log->getDeadPlayer();
                    if ($player) {
                        // On normalise uniquement le joueur avec le groupe gameplayer:read
                        $deadPlayers[] = $normalizer->normalize($player, null, [
                            'groups' => ['gameplayer:read']
                        ]);
                    }
                }
            }

            return $this->successResponse($deadPlayers);

        } catch (\Throwable $e) {
            return $this->errorResponse("Erreur lors de la récupération des morts : " . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/reveal', name: 'app_bot_game_reveal', methods: ['POST'])]
    public function revealPlayer(int $id, Request $request, GameRepository $gameRepository, NormalizerInterface $normalizer,RequestPayloadService $payloadService): JsonResponse {
        try {
           
            $game = $gameRepository->find($id);

            if (!$game) {
                return $this->errorResponse("La partie $id n'existe pas.", Response::HTTP_NOT_FOUND);
            }
            
            $payload = $payloadService->extractValidatedPayload($request, ['discordId']);
            $discordId = $payload['discordId']; 

            // Délégation au GameService pour la logique métier
            $this->gameService->revealPlayer($game, $discordId);

            // On normalise le log et le joueur pour le retour bot
            $gameData = $normalizer->normalize($game, null, [
                'groups' => ['game:read']
            ]);

            return $this->successResponse($gameData);

        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->errorResponse("Erreur lors de l'enregistrement du kill : " . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}