<?php

namespace App\Service;

use App\Repository\ShopRepository;
use Doctrine\ORM\EntityManagerInterface;

class ShopService 
{
    private const DEFAULT_PAGE_LIMIT = 10;

    public function __construct(
        private EntityManagerInterface $em,
        private ShopRepository $shopRepository
    ) {}

    /**
     * Récupère les articles paginés de la boutique.
     */
    public function getShopArticles(int $page = 1, string $currency, int $limit = self::DEFAULT_PAGE_LIMIT): array
    {
        // Compter les articles filtrés (pour éviter un total incorrect si currency est appliquée)
        $criteria = [];
        if ($currency) {
            $criteria['currency'] = $currency;
        }

        $total = $this->shopRepository->count($criteria);
        $maxPages = max(1, ceil($total / $limit));

        // Forcer la page dans les limites
        $page = max(1, min($page, $maxPages));

        $offset = ($page - 1) * $limit;

        // Récupération paginée
        $articles = $this->shopRepository->findBy(
            $criteria,
            ['price' => 'ASC'],
            $limit,
            $offset
        );

        return [
            'items' => $articles,
            'page' => $page,
            'total' => $total,
            'pages' => $maxPages,
        ];
    }
}