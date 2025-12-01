<?php

namespace App\Controller;

use App\Repository\ItemRepository;
use App\Service\Auth\DiscordUserManager;
use App\Service\EconomyService;
use App\Service\RequestPayloadService;
use App\Service\ShopService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bot/shop')]
final class BotShopController extends AbstractController
{
    private ShopService $shopService;
    private EconomyService $economyService;
    private DiscordUserManager $discordUserService;
    private ItemRepository $itemRepository;

    public function __construct(ShopService $shopService, EconomyService $economyService, DiscordUserManager $discordUserService, ItemRepository $itemRepository)
    {
        $this->shopService = $shopService;
        $this->economyService = $economyService;
        $this->discordUserService = $discordUserService;
        $this->itemRepository = $itemRepository;
    }

    #[Route('/view', name: 'app_bot_shop_view', methods: ['GET'])]
    public function view(Request $request): JsonResponse
    {
        // Extraction des paramètres envoyées par le bot
        $page = max(1, (int) $request->query->get('page', 1));
        $currency = $request->query->get('currency');

        $articles = $this->shopService->getArticles($page, $currency);

        return $this->json($articles);
    }

    #[Route('/buy', name: 'app_bot_shop_buy', methods: ['POST'])]
    public function buy(Request $request, RequestPayloadService $payloadService): JsonResponse
    {
        // Extraction et validation des données JSON envoyées par le bot
        $payload = $payloadService->extractValidatedPayload($request, ['discordId', 'itemId']);

        $user = $this->discordUserService->findOrCreateUserByDiscordId($payload['discordId']);
        $item = $this->itemRepository->find($payload['itemId']);

        if (!$item) {
            return $this->json([
                'error' => 'Item introuvable.'
            ], 404);
        }

        // Achat de l'article
        $result = $this->shopService->buyArticle($user, $item);

        return $this->json($result);
    }
}
