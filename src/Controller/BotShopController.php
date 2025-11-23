<?php

namespace App\Controller;

use App\Service\ShopService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bot/shop')]
final class BotShopController extends AbstractController
{
    private ShopService $shopService;

    public function __construct(ShopService $shopService)
    {
        $this->shopService = $shopService;
    }

    #[Route('/view', name: 'app_bot_shop')]
    public function view(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $currency = $request->query->get('currency');

        $artcles = $this->shopService->getShopArticles($page, $currency);

        return $this->json($artcles);
    }
}
