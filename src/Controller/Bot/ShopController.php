<?php

namespace App\Controller\Bot;

use App\Exception\EconomyException;
use App\Exception\InvalidPayloadException;
use App\Repository\ItemRepository;
use App\Service\Auth\DiscordUserManager;
use App\Service\RequestPayloadService;
use App\Service\ShopService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[Route('/bot/shop')]
final class ShopController extends AbstractBotController
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

        return $this->successResponse($articles);
    }

    #[Route('/viewall', name: 'app_bot_shop_viewall', methods: ['GET'])]
    public function viewAll(Request $request): JsonResponse
    {
        $articles = $this->shopService->getAllArticles();

        return $this->successResponse($articles);
    }

    #[Route('/buy', name: 'app_bot_shop_buy', methods: ['POST'])]
    public function buy(Request $request, RequestPayloadService $payloadService): JsonResponse
    {
        try {
            // Extraction et validation des données JSON envoyées par le bot
            $payload = $payloadService->extractValidatedPayload($request, ['discordId', 'itemId']);

            $user = $this->discordUserService->findOrCreateUserByDiscordId($payload['discordId']);
            $item = $this->itemRepository->find($payload['itemId']);

            if (!$item) {
                throw new EconomyException('Item introuvable.', Response::HTTP_NOT_FOUND);
            }

            // Achat de l'article
            $result = $this->shopService->buyArticle($user, $item);

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

    #[Route('/detail', name: 'app_bot_shop_detail', methods: ['POST'])]
    public function detail(Request $request, RequestPayloadService $payloadService): JsonResponse
    {
        try {
        // Extraction et validation des données JSON envoyées par le bot
        $payload = $payloadService->extractValidatedPayload($request, ['itemId']);

        $item = $this->itemRepository->find($payload['itemId']);

        if (!$item) {
            throw new EconomyException('Item introuvable.', Response::HTTP_NOT_FOUND);
        }

        $result = $this->normalizer->normalize(
            $item,
            null,
            ['groups' => ['item:read']]
        );

        return $this->json($result);

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
