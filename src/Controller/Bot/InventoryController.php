<?php

namespace App\Controller\Bot;

use App\Entity\Item;
use App\Exception\EconomyException;
use App\Exception\InvalidPayloadException;
use App\Service\Auth\DiscordUserManager;
use App\Service\InventoryService;
use App\Service\RequestPayloadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bot/inventory')]
final class InventoryController extends AbstractBotController
{
    public function __construct(
        private InventoryService $inventoryService, 
        private DiscordUserManager $discordUserService, 
        private EntityManagerInterface $em
    ) {}

    #[Route('/view', name: 'app_bot_inventory_view', methods: ['POST'])]
    public function view(Request $request, RequestPayloadService $payloadService): JsonResponse
    {
        try {
            // Extraction et validation des données JSON envoyées par le bot
            $payload = $payloadService->extractValidatedPayload($request, ['discordId']);

            $user = $this->discordUserService->findOrCreateUserByDiscordId($payload['discordId']);

            $items = $this->inventoryService->getUserInventory($user);

            return $this->successResponse(['items' => $items]);
        } catch (InvalidPayloadException $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->errorResponse("Erreur interne du serveur.", Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/sell', name: 'app_bot_inventory_sell', methods: ['POST'])]
    public function sell(Request $request, RequestPayloadService $payloadService): JsonResponse
    {
        try {
            // Extraction et validation des données JSON envoyées par le bot
            $payload = $payloadService->extractValidatedPayload($request, ['sellerId','buyerId','itemId', 'currency', 'price']);

            $seller = $this->discordUserService->findOrCreateUserByDiscordId($payload['sellerId']);
            $buyer = $this->discordUserService->findOrCreateUserByDiscordId($payload['buyerId']);
            $item = $this->em->getRepository(Item::class)->find($payload['itemId']);
            $currency = $payload['currency'];

            if (!$item) {
                throw new EconomyException('Item introuvable.', Response::HTTP_NOT_FOUND);
            }

            $result = $this->inventoryService->sellItem($seller, $buyer, $item, $currency, (int)$payload['price']
            );

            return $this->successResponse([$result]);
        } catch (InvalidPayloadException $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (EconomyException $e) {
            $statusCode = $e->getCode() ?: Response::HTTP_BAD_REQUEST;
            return $this->errorResponse($e->getMessage(), $statusCode);
        } catch (\Throwable $e) {
            return $this->errorResponse("Erreur interne du serveur.", Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}