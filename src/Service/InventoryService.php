<?php

namespace App\Service;

use App\Entity\Inventory;
use App\Entity\Item;
use App\Entity\User;
use App\Enum\TransactionType;
use App\Exception\EconomyException;
use App\Repository\InventoryRepository;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class InventoryService
{
    public function __construct(
        private InventoryRepository $inventoryRepository, 
        private NormalizerInterface $normalizer, 
        private EntityManagerInterface $em,
        private TransactionRepository $transactionRepository,
        private EconomyService $economyService
    ) {}

    /**
     * Récupère uniquement les "items" (pas les rôles) d’un joueur via son Discord ID.
     */
    public function getUserInventory(User $user): array
    {
        // Récupère les items de l'utilisateur avec type = "item"
        $inventories = $this->inventoryRepository->findItemsByUser($user);

        return $this->normalizer->normalize(
            $inventories,
            null,
            ['groups' => ['inventory:read']]
        );

    }

    /**
     * @throws EconomyException
     */
    public function sellItem(User $seller, User $buyer, Item $item, string $currency, int $price): array
    {
        if ($seller->getId() === $buyer->getId()) {
            throw new EconomyException("Vous ne pouvez pas vous vendre un item à vous-même.");
        }

        // Vérifier que le vendeur possède bien l’item
        $sellerInventory = $seller->getInventoryForItem($item);
        if (!$sellerInventory || $sellerInventory->getQuantity() < 1) {
            throw new EconomyException("Vous ne possédez pas cet item.");
        }

        // Vérifier que l’acheteur a assez d'argent
        $buyerBalance = ($currency === 'gems') ? $buyer->getGems() : $buyer->getRubies();
        if ($buyerBalance < $price) {
            throw new EconomyException("L'acheteur n'a pas assez de fonds pour acheter cet item.");
        }

        // Vérifier la dernière transaction entre les deux joueurs
        $lastTrade = $this->transactionRepository->getLastTradeBetweenUsers($seller, $buyer);
        if ($lastTrade) {
            $nextPossible = (clone $lastTrade->getCreatedAt())->modify('+7 days');

            if (new \DateTime() < $nextPossible) {
                $timestamp = $nextPossible->getTimestamp();
                throw new EconomyException(
                    "Une vente entre ces deux joueurs a déjà eu lieu récemment.\n Vous pourrez de nouveau en réaliser une <t:$timestamp:R> (à <t:$timestamp:t>)."
                );
            }
        }

        // Mise à jour de l'argent des deux joueurs
        if($currency === 'gems') {
            $buyer->setGems($buyer->getGems() - $price);
            $seller->setGems($seller->getGems() + $price);
        } else {
            $buyer->setRubies($buyer->getRubies() - $price);
            $seller->setRubies($seller->getRubies() + $price);
        }

        // Mise à jour de l'inventaire vendeur
        if ($sellerInventory->getQuantity() > 1) {
            $sellerInventory->setQuantity($sellerInventory->getQuantity() - 1);
        } else {
            $this->em->remove($sellerInventory);
        }

        // Mise à jour de l'inventaire acheteur
        $buyerInventory = $buyer->getInventoryForItem($item);

        if ($buyerInventory) {
            $buyerInventory->setQuantity($buyerInventory->getQuantity() + 1);
        } else {
            $buyerInventory = new Inventory();
            $buyerInventory->setOwner($buyer);
            $buyerInventory->setItem($item);
            $buyerInventory->setQuantity(1);
            $this->em->persist($buyerInventory);
        }

        $this->em->flush();

        // Enregistrer les transactions 
        $this->economyService->createTransaction(
            TransactionType::SELL,
            $currency === 'gems' ? 'gems' : 'rubies',
            $price,
            $seller,
            $buyer,
            $item->getName()
        );

        $this->economyService->createTransaction(
            TransactionType::PURCHASE,
            $currency === 'gems' ? 'gems' : 'rubies',
            $price,
            $buyer,
            $seller,
            $item->getName()
        );

        return [
            'message' => "<@{$buyer->getDiscordId()}> a acheté l'item « __{$item->getName()}__ » de <@{$seller->getDiscordId()}>.",
        ];
    }
}

