<?php

namespace App\Controller;

use App\Repository\ItemRepository;
use App\Service\Auth\DiscordUserManager;
use App\Service\RequestPayloadService;
use App\Service\ShopService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[Route('/bot/shop')]
final class BotShopController extends AbstractController
{
    private ShopService $shopService;
    private DiscordUserManager $discordUserService;
    private ItemRepository $itemRepository;
    private NormalizerInterface $normalizer;

    public function __construct(ShopService $shopService, DiscordUserManager $discordUserService, ItemRepository $itemRepository, NormalizerInterface $normalizer)
    {
        $this->shopService = $shopService;
        $this->discordUserService = $discordUserService;
        $this->itemRepository = $itemRepository;
        $this->normalizer = $normalizer;
    }

    #[Route('/view', name: 'app_bot_shop_view', methods: ['GET'])]
    public function view(Request $request): JsonResponse
    {
        // Extraction des paramètres envoyées par le bot
        $page = max(1, (int) $request->query->get('page', 1));
        $currency = $request->query->get('currency');

        $articles = $this->shopService->getArticlesByCurrency($page, $currency);

        return $this->json($articles);
    }

    #[Route('/viewall', name: 'app_bot_shop_viewall', methods: ['GET'])]
    public function viewAll(Request $request): JsonResponse
    {
        $articles = $this->shopService->getAllArticles();

        return $this->json($articles);
    }

    #[Route('/buy', name: 'app_bot_shop_buy', methods: ['POST'])]
    public function buy(Request $request, RequestPayloadService $payloadService): JsonResponse
    {
        // Extraction et validation des données JSON envoyées par le bot
        $payload = $payloadService->extractValidatedPayload($request, ['discordId', 'itemId']);
        if ($payload instanceof JsonResponse) return $payload;

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

    #[Route('/detail', name: 'app_bot_shop_detail', methods: ['POST'])]
    public function detail(Request $request, RequestPayloadService $payloadService): JsonResponse
    {
        // Extraction et validation des données JSON envoyées par le bot
        $payload = $payloadService->extractValidatedPayload($request, ['itemId']);
        if ($payload instanceof JsonResponse) return $payload;

        $item = $this->itemRepository->find($payload['itemId']);

        if (!$item) {
            return $this->json([
                'error' => 'Item introuvable.'
            ], 404);
        }

        $result = $this->normalizer->normalize(
            $item,
            null,
            ['groups' => ['item:read']]
        );

        return $this->json($result);
    }
}
