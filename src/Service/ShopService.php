<?php

namespace App\Service;

use App\Entity\Inventory;
use App\Entity\Item;
use App\Entity\User;
use App\Enum\TransactionType;
use App\Exception\EconomyException;
use App\Repository\ItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ShopService 
{
    private const DEFAULT_PAGE_LIMIT = 5;

    public function __construct(
        private EntityManagerInterface $em,
        private ItemRepository $itemRepository,
        private NormalizerInterface $normalizer,
        private EconomyService $economyService
    ) {}

    /**
     * Récupère les articles paginés de la boutique.
     */
    public function getArticlesByCurrency(int $page = 1, string $currency, int $limit = self::DEFAULT_PAGE_LIMIT): array
    {
        // Compter les articles filtrés (pour éviter un total incorrect si currency est appliquée)
        $criteria = [];
        if ($currency) {
            $criteria['currency'] = $currency;
        }

        $total = $this->itemRepository->count($criteria);
        $maxPages = max(1, ceil($total / $limit));

        // Forcer la page dans les limites
        $page = max(1, min($page, $maxPages));

        $offset = ($page - 1) * $limit;

        // Récupération paginée
        $articles = $this->itemRepository->findBy(
            $criteria,
            ['position' => 'ASC', 'price' => 'ASC'],
            $limit,
            $offset
        );

        $articlesNormalized = $this->normalizer->normalize(
            $articles,
            null,
            ['groups' => ['item:read']]
        );

        return [
            'items' => $articlesNormalized,
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => $maxPages,
                'totalItems' => $total
            ]
        ];
    }

    /**
     * Récupère tous les articles articles de la boutique.
     */
    public function getAllArticles(): array
    {
        $articles = $this->itemRepository->findAll();

        return $articles;
    }

    /**
     * Acheter un article de la boutique.
     */
    public function buyArticle(User $user, Item $item): array
    {
        // Vérifier la quantité disponible
        if ($item->getQuantity() !== null && $item->getQuantity() <= 0) {
            throw new EconomyException("Cet article n’est plus disponible.", Response::HTTP_BAD_REQUEST);
        }

        $currency = $item->getCurrency()->value;
        $price = $item->getPrice();

        // Vérifier que l'utilisateur a les fonds nécessaires
        if ($currency === 'gems' && $user->getGems() < $price) {
            throw new EconomyException("Vous n'avez pas assez de Gemmes pour acheter cet article.", Response::HTTP_BAD_REQUEST);
        }

        if ($currency === 'rubies' && $user->getRubies() < $price) {
            throw new EconomyException("Vous n'avez pas assez de Rubis pour acheter cet article.", Response::HTTP_BAD_REQUEST);
        }

        // Vérifier que l'utilisateur n'a pas déjà atteint la limite d'achat
        if ($item->getPurchaseLimit() !== null) {
            $inventory = $user->getInventoryForItem($item);
            $currentQty = $inventory ? $inventory->getQuantity() : 0;

            if ($currentQty >= $item->getPurchaseLimit()) {
                throw new EconomyException("Vous avez atteint la limite d'achat pour cet article.", Response::HTTP_BAD_REQUEST);
            }
        }

        // Vérifier prérequis
        if ($item->getRequiredItem()) {
            $required = $item->getRequiredItem();

            if (!$user->hasItem($required)) {
                throw new EconomyException("Vous devez posséder « __{$required->getName()}__ » avant d’acheter cet article.", Response::HTTP_BAD_REQUEST);
            }

            $requiredInventory = $user->getInventoryForItem($required);

            if ($requiredInventory) {
                $qty = $requiredInventory->getQuantity();

                if ($qty > 1) {
                    $requiredInventory->setQuantity($qty - 1);
                } else {
                    $this->em->remove($requiredInventory);
                    $user->removeInventory($requiredInventory);
                }
            }
        }

        // Débiter l'utilisateur
        if ($currency === 'gems') {
            $user->setGems($user->getGems() - $price);
        } else {
            $user->setRubies($user->getRubies() - $price);
        }

        // Réduire la quantité de l'article
        if ($item->getQuantity() !== null) {
            $item->setQuantity($item->getQuantity() - 1);
        }

        // Gestion de l'inventaire
        $inventory = $user->getInventoryForItem($item);

        if ($inventory) {
            $inventory->setQuantity($inventory->getQuantity() + 1);
        } else {
            $inventory = new Inventory();
            $inventory->setOwner($user);
            $inventory->setItem($item);
            $inventory->setQuantity(1);

            $this->em->persist($inventory);
        }

        $this->em->flush();
        
        $this->economyService->createTransaction(TransactionType::PURCHASE, $currency, $price, $user, null, $item->getName());

        return [
            'message' => "Achat de « __{$item->getName()}__ » effectué avec succès !",
        ];
    }
}