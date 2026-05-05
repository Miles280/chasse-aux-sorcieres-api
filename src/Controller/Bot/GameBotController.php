<?php

namespace App\Controller\Bot;

use App\Service\GameCompositionService;
use App\Service\RequestPayloadService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bot/game')]
final class GameBotController extends AbstractBotController
{
    public function __construct(
        private GameCompositionService $gameCompositionService
    ) {}

    #[Route('/{id}/composition/add', name: 'app_bot_game_composition_add', methods: ['POST'])]
    public function addComposition(int $id, Request $request, RequestPayloadService $payloadService): JsonResponse
    {
        try {
            // On attend un JSON : {"roleIds": [1, 5]}
            $payload = $payloadService->extractValidatedPayload($request, ['roleIds']);
            
            // On récupère l'entité Game mise à jour
            $game = $this->gameCompositionService->addRoles($id, $payload['roleIds']);

            // On prépare la liste des rôles pour le bot
            $compositionData = [];
            foreach ($game->getCompositions() as $comp) {
                $role = $comp->getRole();
                $compositionData[] = [
                    'id' => $role->getId(),
                    'name' => $role->getName(),
                    'alignments' => $role->getAlignments(),
                    'camp' => $role->getCamp()->value, 
                ];
            }

            return $this->successResponse([
                'gameId' => $game->getId(),
                'composition' => $compositionData 
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}/composition/remove', name: 'app_bot_game_composition_remove_bulk', methods: ['POST'])]
    public function removeComposition(int $id, Request $request, RequestPayloadService $payloadService): JsonResponse
    {
        try {
            // On attend un JSON : {"roleIds": [1, 5]}
            $payload = $payloadService->extractValidatedPayload($request, ['roleIds']);
            
            $game = $this->gameCompositionService->removeRoles($id, $payload['roleIds']);

            // On prépare la nouvelle composition pour le bot
            $compositionData = [];
            foreach ($game->getCompositions() as $comp) {
                $role = $comp->getRole();
                $compositionData[] = [
                    'id' => $role->getId(),
                    'name' => $role->getName(),
                    'alignments' => $role->getAlignments(),
                    'camp' => $role->getCamp()->value,
                ];
            }

            return $this->successResponse([
                'gameId' => $game->getId(),
                'composition' => $compositionData
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}