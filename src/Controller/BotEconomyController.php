<?php

namespace App\Controller;

use App\Entity\User;
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
    private UserRepository $userRepo;
    private TransactionRepository $transactionRepo;
    private $em;

    public function __construct(UserRepository $userRepo, TransactionRepository $transactionRepo, EntityManagerInterface $em)
    {
        $this->userRepo = $userRepo;
        $this->transactionRepo = $transactionRepo;
        $this->em = $em;
    }


    #[Route('/{discordId}', name: 'app_bot_view', methods: ['GET'])]
    public function view(string $discordId): JsonResponse
    {
        // Récupération en base de données de l'utilisateur demandé  
        $user = $this->userRepo->findOneBy(['discordId' => $discordId]);

        // Création de l'utilisateur en base de données s'il n'existe pas déjà
        if (!$user) {
            $user = new User();
            $user->setDiscordId($discordId);

            $this->em->persist($user);
            $this->em->flush();
        }

        // Récupération des 5 dernières transactions de l'utilisateur
        $transactions = $this->transactionRepo->findBy(
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

        // Réponse en JSON
        return $this->json([
            'discordId' => $user->getDiscordId(),
            'gems' => $user->getGems(),
            'rubies' => $user->getRubies(),
            'transactions' => $transactionsData
        ]);
    }

    #[Route('/give', name: 'app_bot_give', methods: ['POST'])]
    public function give(Request $request, EntityManagerInterface $em, UserRepository $userRepository): JsonResponse
    {
        // {
        //     "from": "123456789012345678",
        //     "to": "876543210987654321",
        //     "currency": "gems",
        //     "amount": 50
        // }

        // On récupère les données JSON envoyées par le bot
        $data = json_decode($request->getContent(), true);
        $fromId = $data['from'] ?? null;
        $toId = $data['to'] ?? null;
        $currency = $data['currency'] ?? null;
        $amount = $data['amount'] ?? null;

        // Toutes les vérifications de base
        if (!$fromId || !$toId || !$currency || !$amount) {
        return $this->json(['error' => 'Requête invalide, champs manquants.'], 400);
        }

        if (!in_array($currency, ['gems', 'rubies'])) {
            return $this->json(['error' => 'Monnaie invalide.'], 400);
        }

        if ($amount <= 0) {
            return $this->json(['error' => 'Le montant doit être supérieur à zéro.'], 400);
        }

        if ($fromId === $toId) {
            return $this->json(['error' => 'Un utilisateur ne peut pas se donner de monnaie à lui-même.'], 400);
        }

        // Récupérer les utilisateurs
        $sender = $userRepository->findOneBy(['discordId' => $fromId]);
        $receiver = $userRepository->findOneBy(['discordId' => $toId]);

        if (!$sender || !$receiver) {
            return $this->json(['error' => 'Utilisateur introuvable.'], 404);
        }

        // Récupération des soldes de comptes et vérification que l'éxpéditeur a assez de monnaie
        $senderBalance = $currency === 'gems' ? $sender->getGems() : $sender->getRubies();
        $receiverBalance = $currency === 'gems' ? $receiver->getGems() : $receiver->getRubies();

        if ($senderBalance < $amount) {
            return $this->json(['error' => 'Solde insuffisant.'], 400);
        }

        // Mise à jour des soldes*
        if ($currency === 'gems') {
            $sender->setGems($senderBalance - $amount);
            $receiver->setGems($receiverBalance + $amount);
        } else {
            $sender->setRubies($senderBalance - $amount);
            $receiver->setRubies($receiverBalance + $amount);
        }

        $em->persist($sender);
        $em->persist($receiver);
        $em->flush();

        // Réponse en JSON
        return $this->json([
            'mesage'=> 'Transaction effectuée avec succès.',
            'from' => [
            'id' => $sender->getDiscordId(),
            'balance' => [
                'gems' => $sender->getGems(),
                'rubies' => $sender->getRubies(),
            ],
        ],
        'to' => [
            'id' => $receiver->getDiscordId(),
            'balance' => [
                'gems' => $receiver->getGems(),
                'rubies' => $receiver->getRubies(),
            ],
        ],
        ]);
    }

    #[Route('/add', name: 'app_bot_add', methods: ['POST'])]
    public function add(): JsonResponse
    {
        // {
        //     "discordId": "876543210987654321",
        //     "currency": "rubies",
        //     "amount": 10
        // }


        // Réponse en JSON
        return $this->json([
            'mesage'=> 'Transaction effectuée'
        ]);
    }

    #[Route('/remove', name: 'app_bot_remove', methods: ['POST'])]
    public function remove(): JsonResponse
    {
        // {
        //     "discordId": "876543210987654321",
        //     "currency": "gems",
        //     "amount": 25
        // }

        // Réponse en JSON
        return $this->json([
            'mesage'=> 'Transaction effectuée'
        ]);
    }
    
    #[Route('/set', name: 'app_bot_set', methods: ['POST'])]
    public function set(): JsonResponse
    {
        // {
        //     "discordId": "876543210987654321",
        //     "currency": "rubies",
        //     "value": 100
        // }

        // Réponse en JSON
        return $this->json([
            'mesage'=> 'Transaction effectuée'
        ]);
    }
}
