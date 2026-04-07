<?php

namespace App\Controller\Bot;

use App\Entity\Role;
use App\Enum\Camp;
use App\Repository\RoleRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bot/roles')]
final class RoleController extends AbstractBotController
{
    /**
     * Récupérer tous les rôles
     */
    #[Route('', name: 'app_bot_roles_index', methods: ['GET'])]
    public function index(RoleRepository $roleRepository): JsonResponse
    {
        try {
            $roles = $roleRepository->findAll();

            return $this->successResponse($roles, 200, ['groups' => ['role:read']]);
        } catch (\Exception $e) {
            return $this->errorResponse("Erreur lors de la récupération des rôles.");
        }
    }

    /**
     * Récupérer les rôles selon un camp spécifique
     */
    #[Route('/camp/{campValue}', name: 'app_bot_roles_by_camp', methods: ['GET'])]
    public function byCamp(string $campValue, RoleRepository $roleRepository): JsonResponse
    {
        try {
            $camp = Camp::tryFrom($campValue);
            if (!$camp) {
                return $this->errorResponse(sprintf('Le camp "%s" n\'existe pas.', $campValue), 404);
            }

            $roles = $roleRepository->findBy(['camp' => $camp]);

            return $this->successResponse($roles, 200, ['groups' => ['role:read']]);
        } catch (\Exception $e) {
            return $this->errorResponse("Erreur lors de la récupération des rôles par camp.");
        }
    }

    /**
     * Récupérer un rôle précis (par son ID)
     */
    #[Route('/{id}', name: 'app_bot_roles_show', methods: ['GET'])]
    public function show(?Role $role): JsonResponse
    {
        try {
            if (!$role) {
                return $this->errorResponse('Rôle introuvable.', 404);
            }

            return $this->successResponse($role, 200, ['groups' => ['role:read']]);

        } catch (\Exception $e) {
            return $this->errorResponse("Erreur lors de la récupération du rôle.");
        }
    }
}