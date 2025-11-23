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
     * Récupère les articles paginées de la boutique.
     */
    public function getShopArticles(int $page = 1, string $currency, int $limit = self::DEFAULT_PAGE_LIMIT): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $limit;

        $criteria = [];
        if ($currency) {
            $criteria['currency'] = $currency; // filtre sur la colonne currency
        }

        // Récupération paginée
        $articles = $this->shopRepository->findBy(
            $criteria,                 // filtre
            ['price' => 'ASC'],        // tri 
            $limit,                    // Limite
            $offset                    // Offset
        );

        // Nombre total d'articles
        $total = $this->shopRepository->count([]);

        return [
            'items' => $articles,
            'page' => $page,
            'total' => $total,
            'pages' => ceil($total / $limit),
        ];
    }
}