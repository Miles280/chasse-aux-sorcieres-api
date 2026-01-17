<?php

namespace App\Controller\Bot;

use App\Entity\Item;
use App\Service\Auth\DiscordUserManager;
use App\Service\InventoryService;
use App\Service\RequestPayloadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bot/inventory')]
final class InventoryController extends AbstractController
{
    public function __construct(
        private InventoryService $inventoryService, 
        private DiscordUserManager $discordUserService, 
        private EntityManagerInterface $em
    ) {}

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

    #[Route('/sell', name: 'app_bot_inventory_sell', methods: ['POST'])]
    public function sell(Request $request, RequestPayloadService $payloadService): JsonResponse
    {
        // Extraction et validation des données JSON envoyées par le bot
        $payload = $payloadService->extractValidatedPayload($request, ['sellerId','buyerId','itemId', 'currency', 'price']);
        if ($payload instanceof JsonResponse) return $payload;

        $seller = $this->discordUserService->findOrCreateUserByDiscordId($payload['sellerId']);
        $buyer = $this->discordUserService->findOrCreateUserByDiscordId($payload['buyerId']);
        $item = $this->em->getRepository(Item::class)->find($payload['itemId']);
        $currency = $payload['currency'];

        if (!$item) {
            return $this->json(['error' => "Item introuvable."], 404);
        }

        $result = $this->inventoryService->sellItem($seller, $buyer, $item, $currency, (int)$payload['price']
        );

        return $this->json($result);
    }
}
