<?php

namespace App\Controller;

use App\Service\Auth\DiscordUserManager;
use App\Service\InventoryService;
use App\Service\RequestPayloadService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bot/inventory')]
final class BotInventoryController extends AbstractController
{
    private InventoryService $inventoryService;
    private DiscordUserManager $discordUserService;

    public function __construct(InventoryService $inventoryService, DiscordUserManager $discordUserService)
    {
        $this->inventoryService = $inventoryService;
        $this->discordUserService = $discordUserService;
    }

    #[Route('/view', name: 'app_bot_inventory_view', methods: ['POST'])]
    public function view(Request $request, RequestPayloadService $payloadService): JsonResponse
    {
        // Extraction et validation des données JSON envoyées par le bot
        $payload = $payloadService->extractValidatedPayload($request, ['discordId']);
        if ($payload instanceof JsonResponse) return $payload;

        $user = $this->discordUserService->findOrCreateUserByDiscordId($payload['discordId']);

        $items = $this->inventoryService->getUserInventory($user);

        return $this->json([
            'items' => $items
        ]);
    }
}
