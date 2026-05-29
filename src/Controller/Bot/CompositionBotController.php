<?php

namespace App\Controller\Bot;

use App\Repository\GameRepository;
use App\Service\CompositionService;
use App\Service\RequestPayloadService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bot/composition')]
final class CompositionBotController extends AbstractBotController
{
    public function __construct(
        private CompositionService $gameCompositionService
    ) {}

    #[Route('/{id}', name: 'app_bot_game_composition_view', methods: ['GET'])]
    public function viewComposition(int $id, GameRepository $gameRepository): JsonResponse
    {
        try {          
            // On cherche la partie. Si elle n'existe pas, on le signale.
            $game = $gameRepository->find($id);

            if (!$game) {
                return $this->errorResponse("La partie $id n'existe pas.", Response::HTTP_NOT_FOUND);
            }

            // On réutilise notre méthode de formatage
            return $this->successResponse([
                'composition' => $this->gameCompositionService->formatComposition($game) 
            ]);
            
        } catch (\Throwable $e) {
            return $this->errorResponse("Erreur lors de la récupération : " . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/add', name: 'app_bot_game_composition_add', methods: ['POST'])]
    public function addComposition(int $id, Request $request, RequestPayloadService $payloadService): JsonResponse
    {
        try {
            // On attend un JSON : {"roleIds": [1, 5]}
            $payload = $payloadService->extractValidatedPayload($request, ['roleIds']);
            
            // On récupère l'entité Game mise à jour
            $game = $this->gameCompositionService->addRoles($id, $payload['roleIds']);

            return $this->successResponse([
                'composition' => $this->gameCompositionService->formatComposition($game) 
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}/remove', name: 'app_bot_game_composition_remove', methods: ['POST'])]
    public function removeComposition(int $id, Request $request, RequestPayloadService $payloadService): JsonResponse
    {
        try {
            // On attend un JSON : {"roleIds": [1, 5]}
            $payload = $payloadService->extractValidatedPayload($request, ['roleIds']);
            
            $game = $this->gameCompositionService->removeRoles($id, $payload['roleIds']);

            return $this->successResponse([
                'composition' => $this->gameCompositionService->formatComposition($game) 
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}/reset', name: 'app_bot_game_composition_reset', methods: ['GET'])]
    public function resetComposition(int $id): JsonResponse
    {
        try {          
            $game = $this->gameCompositionService->resetRoles($id);

            return $this->successResponse([
                'composition' => $this->gameCompositionService->formatComposition($game)
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}