<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Transaction;
use App\Enum\Currency;
use App\Enum\TransactionType;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;

class CasinoService
{

    public function __construct(
        private EntityManagerInterface $em,
        private TransactionRepository $transactionRepository
    ) {}

    public function processCasinoTransaction(User $user, int $amount, string $operation): ?array
    {
        // 1. Calcul du montant signé (positif si gain, négatif si perte)
        // L'input $amount est toujours positif, $operation détermine le signe.
        $signedAmount = ($operation === 'add') ? $amount : -$amount;

        // 2. Mise à jour du solde de l'utilisateur (C'est immédiat)
        $newBalance = $user->getRubies() + $signedAmount;
        if ($newBalance < 0) return ['error' => "Pas assez de Rubis."];
        $user->setRubies($newBalance);
        

        // 3. Gestion intelligente de la transaction
        // On cherche la DERNIÈRE transaction CASINO de cet utilisateur
        $lastTx = $this->transactionRepository->findOneBy(
            ['owner' => $user, 'type' => TransactionType::CASINO, 'currency' => Currency::RUBIES],
            ['createdAt' => 'DESC'] 
        );

        $now = new \DateTimeImmutable();
        $oneHourAgo = $now->modify('-1 hour');

        // Est-ce qu'on a une transaction récente (- de 1h) ?
        if ($lastTx && $lastTx->getCreatedAt() > $oneHourAgo) {
            // OUI : On met à jour l'existante (Fusion)
            $newTxAmount = $lastTx->getAmount() + $signedAmount;
            
            $lastTx->setAmount($newTxAmount);
            $lastTx->setCreatedAt($now); // On "refresh" la date pour prolonger la session
        } else {
            // NON : On crée une nouvelle transaction
            $transaction = new Transaction();
            $transaction->setOwner($user);
            $transaction->setType(TransactionType::CASINO);
            $transaction->setCurrency(Currency::RUBIES); // Assure-toi que ton Enum Currency match
            $transaction->setAmount($signedAmount);
            $transaction->setCreatedAt($now);
            
            $this->em->persist($transaction);
        }

        // 4. Sauvegarde globale
        $this->em->flush();

        return null; 
    }
}
