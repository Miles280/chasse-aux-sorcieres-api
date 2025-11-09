<?php

namespace App\Service;

use App\Entity\Transaction;
use App\Entity\User;
use App\Enum\Currency;
use App\Enum\TransactionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class EconomyService
{
    public function __construct(private EntityManagerInterface $em) {}
    
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
}
