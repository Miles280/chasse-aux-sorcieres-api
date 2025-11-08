<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Entity\User;
use App\Enum\Currency;
use App\Enum\TransactionType;
use App\Repository\TransactionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bot/economy')]
final class BotEconomyController extends AbstractController
{
    private UserRepository $userRepository;
    private TransactionRepository $transactionRepository;
    private EntityManagerInterface $em;

    public function __construct(UserRepository $userRepository, TransactionRepository $transactionRepository, EntityManagerInterface $em)
    {
        $this->userRepository = $userRepository;
        $this->transactionRepository = $transactionRepository;
        $this->em = $em;
    }


    #[Route('/{discordId}', name: 'app_bot_view', methods: ['GET'])]
    public function view(string $discordId): JsonResponse
    {
        // Récupération en base de données de l'utilisateur demandé  
        $user = $this->userRepository->findOneBy(['discordId' => $discordId]);

        // Création de l'utilisateur en base de données s'il n'existe pas déjà
        if (!$user) {
            $user = new User();
            $user->setDiscordId($discordId);

            $this->em->persist($user);
            $this->em->flush();
        }

        // Récupération des 5 dernières transactions de l'utilisateur
        $transactions = $this->transactionRepository->findBy(
            ['owner' => $user], 
            ['createdAt' => 'DESC'], 
            5
        );

        // Transformation des transactions en tableau pour le JSON
        $transactionsData = array_map(function ($transaction) {
            return [
                'id' => $transaction->getId(),
                'type' => $transaction->getType()->value,
                'currency' => $transaction->getCurrency()->value,
                'amount' => $transaction->getAmount(),
                'description' => $transaction->getDescription(),
                'relatedUserId' => $transaction->getRelatedUser()?->getDiscordId(),
                'createdAt' => $transaction->getCreatedAt()->getTimestamp(),
            ];
        }, $transactions);

        return $this->json([
            'discordId' => $user->getDiscordId(),
            'gems' => $user->getGems(),
            'rubies' => $user->getRubies(),
            'transactions' => $transactionsData
        ]);
    }

    #[Route('/give', name: 'app_bot_give', methods: ['POST'])]
    public function give(Request $request): JsonResponse
    {
        // Extraction des données JSON envoyées par le bot
        $payload = json_decode($request->getContent(), true);
        $fromId   = $payload['from'] ?? null;
        $toId     = $payload['to'] ?? null;
        $currency = $payload['currency'] ?? null;
        $amount   = $payload['amount'] ?? null;

        // Vérifications de validité des données reçues
        if (!$fromId || !$toId || !$currency || !$amount) {
            return $this->json(['error' => 'Requête invalide : champs manquants.'], 400);
        }

        if (!in_array($currency, ['gems', 'rubies'], true)) {
            return $this->json(['error' => 'Monnaie invalide.'], 400);
        }

        if (!is_numeric($amount) || $amount <= 0) {
            return $this->json(['error' => 'Le montant doit être un nombre positif.'], 400);
        }

        if ($fromId === $toId) {
            return $this->json(['error' => 'Un utilisateur ne peut pas se donner de monnaie à lui-même.'], 400);
        }

        // Récupération des utilisateurs expéditeur et destinataire
        $sender = $this->userRepository->findOneBy(['discordId' => $fromId]);
        $receiver = $this->userRepository->findOneBy(['discordId' => $toId]);

        if (!$sender || !$receiver) {
            return $this->json(['error' => 'Utilisateur introuvable dans la base de données.'], 404);
        }

        // Vérification du solde de l’expéditeur
        $senderBalance = $currency === 'gems' ? $sender->getGems() : $sender->getRubies();
        if ($senderBalance < $amount) {
            return $this->json(['error' => 'Solde insuffisant.'], 400);
        }

        // Mise à jour des soldes
        if ($currency === 'gems') {
            $sender->setGems($sender->getGems() - $amount);
            $receiver->setGems($receiver->getGems() + $amount);
        } else {
            $sender->setRubies($sender->getRubies() - $amount);
            $receiver->setRubies($receiver->getRubies() + $amount);
        }

        // Création de la transaction pour l'expéditeur
        $transactionSender = new Transaction();
        $transactionSender
            ->setType(TransactionType::DONATION)
            ->setCurrency(Currency::from($currency))
            ->setAmount($amount)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setOwner($sender)
            ->setRelatedUser($receiver);

        // Création de la transaction pour le destinataire
        $transactionReceiver = new Transaction();
        $transactionReceiver
            ->setType(TransactionType::RECEIVE)
            ->setCurrency(Currency::from($currency))
            ->setAmount($amount)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setOwner($receiver)
            ->setRelatedUser($sender);

        $this->em->persist($transactionSender);
        $this->em->persist($transactionReceiver);
        $this->em->persist($sender);
        $this->em->persist($receiver);
        $this->em->flush();

        return $this->json([
            'success' => true,
            'new_balance' => [
                'gems'   => $sender->getGems(),
                'rubies' => $sender->getRubies(),
            ],
        ]);
    }


    #[Route('/add', name: 'app_bot_add', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {   
        // Extraction des données JSON envoyées par le bot
        $payload = json_decode($request->getContent(), true);
        $discordId = $payload['discordId'] ?? null;
        $currency  = $payload['currency'] ?? null;
        $amount    = $payload['amount'] ?? null;

        // Vérifications de validité des données reçues
        if (!$discordId || !$currency || !$amount) {
            return $this->json(['error' => 'Requête invalide : champs manquants.'], 400);
        }

        if (!in_array($currency, ['gems', 'rubies'], true)) {
            return $this->json(['error' => 'Monnaie invalide.'], 400);
        }

        if (!is_numeric($amount) || $amount <= 0) {
            return $this->json(['error' => 'Le montant doit être un nombre positif.'], 400);
        }

        // Récupération de l'utilisateur cible via son identifiant Discord
        $user = $this->userRepository->findOneBy(['discordId' => $discordId]);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur introuvable dans la base de données.'], 404);
        }

        // Mise à jour du solde en fonction de la monnaie spécifiée
        if ($currency === 'gems') {
            $user->setGems($user->getGems() + $amount);
        } else {
            $user->setRubies($user->getRubies() + $amount);
        }

        // Création de la transaction 
        $transaction = new Transaction();
        $transaction
            ->setType(TransactionType::ADMIN)
            ->setCurrency(Currency::from($currency))
            ->setAmount($amount)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setOwner($user);

        $this->em->persist($transaction);
        $this->em->persist($user);
        $this->em->flush();

        return $this->json([
            'success' => true,
            'new_balance' => [
                'gems'   => $user->getGems(),
                'rubies' => $user->getRubies(),
            ],
        ]);
    }

    #[Route('/remove', name: 'app_bot_remove', methods: ['POST'])]
    public function remove(Request $request): JsonResponse
    {
        // Extraction des données JSON envoyées par le bot
        $payload = json_decode($request->getContent(), true);
        $discordId = $payload['discordId'] ?? null;
        $currency  = $payload['currency'] ?? null;
        $amount    = $payload['amount'] ?? null;

        // Vérifications de validité des données reçues
        if (!$discordId || !$currency || !$amount) {
            return $this->json(['error' => 'Requête invalide : champs manquants.'], 400);
        }

        if (!in_array($currency, ['gems', 'rubies'], true)) {
            return $this->json(['error' => 'Monnaie invalide.'], 400);
        }

        if (!is_numeric($amount) || $amount <= 0) {
            return $this->json(['error' => 'Le montant doit être un nombre positif.'], 400);
        }

        // Récupération de l'utilisateur cible via son identifiant Discord
        $user = $this->userRepository->findOneBy(['discordId' => $discordId]);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur introuvable dans la base de données.'], 404);
        }

        // Mise à jour du solde en fonction de la monnaie spécifiée
        if ($currency === 'gems') {
            $user->setGems($user->getGems() - $amount);
        } else {
            $user->setRubies($user->getRubies() - $amount);
        }

        // Création de la transaction 
        $transaction = new Transaction();
        $transaction
            ->setType(TransactionType::ADMIN)
            ->setCurrency(Currency::from($currency))
            ->setAmount(-$amount)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setOwner($user);

        $this->em->persist($transaction);
        $this->em->persist($user);
        $this->em->flush();

        return $this->json([
            'success' => true,
            'new_balance' => [
                'gems'   => $user->getGems(),
                'rubies' => $user->getRubies(),
            ],
        ]);
    }
    
    #[Route('/set', name: 'app_bot_set', methods: ['POST'])]
    public function set(Request $request): JsonResponse
    {
        // Extraction des données JSON envoyées par le bot
        $payload = json_decode($request->getContent(), true);
        $discordId = $payload['discordId'] ?? null;
        $currency  = $payload['currency'] ?? null;
        $amount    = $payload['amount'] ?? null;

        // Vérifications de validité des données reçues
        if (!$discordId || !$currency || !$amount) {
            return $this->json(['error' => 'Requête invalide : champs manquants.'], 400);
        }

        if (!in_array($currency, ['gems', 'rubies'], true)) {
            return $this->json(['error' => 'Monnaie invalide.'], 400);
        }

        if (!is_numeric($amount) || $amount <= 0) {
            return $this->json(['error' => 'Le montant doit être un nombre positif.'], 400);
        }

        // Récupération de l'utilisateur cible via son identifiant Discord
        $user = $this->userRepository->findOneBy(['discordId' => $discordId]);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur introuvable dans la base de données.'], 404);
        }

        // Mise à jour du solde en fonction de la monnaie spécifiée
        if ($currency === 'gems') {
            $user->setGems($amount);
        } else {
            $user->setRubies($amount);
        }
        
        // Création de la transaction 
        $transaction = new Transaction();
        $transaction
            ->setType(TransactionType::SET)
            ->setCurrency(Currency::from($currency))
            ->setAmount($amount)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setOwner($user);

        $this->em->persist($transaction);
        $this->em->persist($user);
        $this->em->flush();

        return $this->json([
            'success' => true,
            'new_balance' => [
                'gems'   => $user->getGems(),
                'rubies' => $user->getRubies(),
            ],
        ]);
    }
}
