<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Transaction;
use App\Enum\Currency;
use App\Enum\TransactionType;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class EconomyService
{
    private const DEFAULT_PAGE_LIMIT = 15;

    public function __construct(
        private EntityManagerInterface $em,
        private TransactionRepository $transactionRepository
    ) {}

    /**
     * Vérifie les données d'une transaction avant traitement.
     */
    public function validateTransactionData(string $currency, $amount): ?JsonResponse
    {
        if (!in_array($currency, ['gems', 'rubies'], true)) {
            return new JsonResponse(['error' => 'Monnaie invalide.'], 400);
        }

        if (!is_numeric($amount) || $amount <= 0) {
            return new JsonResponse(['error' => 'Le montant doit être un nombre positif.'], 400);
        }

        return null;
    }

    /**
     * Crée et enregistre une transaction.
     */
    public function createTransaction(
        TransactionType $type,
        string $currency,
        float $amount,
        User $owner,
        ?User $relatedUser = null
    ): Transaction {
        $transaction = new Transaction();
        $transaction
            ->setType($type)
            ->setCurrency(Currency::from($currency))
            ->setAmount($amount)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setOwner($owner)
            ->setRelatedUser($relatedUser);

        $this->em->persist($transaction);
        $this->em->flush();

        return $transaction;
    }

    /**
     * Récupère la dernière transaction de don de rubis d'un utilisateur.
     */
    public function getLastRubyDonation(User $user): ?Transaction
    {
        return $this->transactionRepository->findLastRubyDonation($user);
    }

    /**
     * Récupère les transactions paginées d'un utilisateur, avec option de filtre par type.
     */
    public function getTransactionHistory(User $user, int $page = 1, array $types = [], int $limit = self::DEFAULT_PAGE_LIMIT): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $limit;

        // Critères de base
        $criteria = ['owner' => $user];

        // Si des types sont fournis, on les ajoute au filtre
        if (!empty($types)) {
            $criteria['type'] = $types;
        }

        // Récupération des transactions selon le filtre et la pagination
        $transactions = $this->transactionRepository->findBy(
            $criteria,
            ['createdAt' => 'DESC'],
            $limit,
            $offset
        );

        // Compte le total filtré
        $total = $this->transactionRepository->count($criteria);

        return [
            'transactions' => $this->formatTransactions($transactions),
            'page' => $page,
            'total' => $total,
            'pages' => ceil($total / $limit),
        ];
    }

    /**
     * Récupère les informations économiques de base d’un utilisateur
     * (solde + 5 dernières transactions).
     */
    public function getUserOverview(User $user): array
    {
        $transactions = $this->transactionRepository->findBy(
            ['owner' => $user],
            ['createdAt' => 'DESC'],
            5
        );

        return [
            'gems' => $user->getGems(),
            'rubies' => $user->getRubies(),
            'transactions' => $this->formatTransactions($transactions),
        ];
    }

    /**
     * Transforme une liste de transactions en tableau simplifié.
     */
    private function formatTransactions(array $transactions): array
    {
        return array_map(fn(Transaction $t) => [
            'id' => $t->getId(),
            'type' => $t->getType()->value,
            'currency' => $t->getCurrency()->value,
            'amount' => $t->getAmount(),
            'description' => $t->getDescription(),
            'relatedUserId' => $t->getRelatedUser()?->getDiscordId(),
            'createdAt' => $t->getCreatedAt()->getTimestamp(),
        ], $transactions);
    }
}
