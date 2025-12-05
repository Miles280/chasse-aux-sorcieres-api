<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\InventoryRepository;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class InventoryService
{
    private InventoryRepository $inventoryRepository;
    private NormalizerInterface $normalizer;

    public function __construct(InventoryRepository $inventoryRepository, NormalizerInterface $normalizer,)
    {
        $this->inventoryRepository = $inventoryRepository;
        $this->normalizer = $normalizer;
    }

    /**
     * Récupère uniquement les "items" (pas les rôles) d’un joueur via son Discord ID.
     */
    public function getUserInventory(User $user): array
    {
        // Récupère les items de l'utilisateur avec type = "item"
        $inventories = $this->inventoryRepository->findItemsByUser($user);

        return $this->normalizer->normalize(
            $inventories,
            null,
            ['groups' => ['inventory:read']]
        );

    }
}
