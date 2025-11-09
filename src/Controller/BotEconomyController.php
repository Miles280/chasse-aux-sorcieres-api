<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Entity\User;
use App\Enum\Currency;
use App\Enum\TransactionType;
use App\Repository\TransactionRepository;
use App\Repository\UserRepository;
use App\Service\DiscordUserService;
use App\Service\EconomyService;
use App\Service\RequestPayloadService;
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
    private EconomyService $economyService;
    private DiscordUserService $discordUserService;

    public function __construct(UserRepository $userRepository, TransactionRepository $transactionRepository, EntityManagerInterface $em, EconomyService $economyService, DiscordUserService $discordUserService)
    {
        $this->userRepository = $userRepository;
        $this->transactionRepository = $transactionRepository;
        $this->em = $em;
        $this->economyService = $economyService;
        $this->discordUserService = $discordUserService;
    }


    #[Route('/{discordId}', name: 'app_bot_view', methods: ['GET'])]
    public function view(string $discordId): JsonResponse
    {
        // Récupération en base de données de l'utilisateur demandé  
        $user = $this->discordUserService->findOrCreateUserByDiscordId($discordId);

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
    public function give(Request $request, RequestPayloadService $payloadService): JsonResponse
    {
        // Extraction et validation des données JSON envoyées par le bot
        $payload = $payloadService->extractValidatedPayload($request, ['senderId', 'receiverId', 'currency', 'amount']);
        if ($payload instanceof JsonResponse) return $payload;
        $senderId = $payload['senderId'];
        $receiverId = $payload['receiverId'];
        $currency = $payload['currency'];
        $amount = $payload['amount'];

        // Vérifications de validité des données reçues
        if ($error = $this->economyService->validateTransactionData($currency, $amount)) {
            return $error; 
        }

        if ($senderId === $receiverId) {
            return $this->json(['error' => 'Un utilisateur ne peut pas se donner de monnaie à lui-même.'], 400);
        }

        // Récupération des utilisateurs expéditeur et destinataire
        $sender = $this->discordUserService->findOrCreateUserByDiscordId($senderId);
        $receiver = $this->discordUserService->findOrCreateUserByDiscordId($receiverId);

        // Vérification du solde de l’expéditeur
        $senderBalance = $currency === 'gems' ? $sender->getGems() : $sender->getRubies();
        if ($senderBalance < $amount) {
            return $this->json(['error' => 'Solde insuffisant.'], 400);
        }

        // Mise à jour des soldes en fonction de la monnaie spécifiée
        if ($currency === 'gems') {
            $sender->setGems($sender->getGems() - $amount);
            $receiver->setGems($receiver->getGems() + $amount);
        } else {
            $sender->setRubies($sender->getRubies() - $amount);
            $receiver->setRubies($receiver->getRubies() + $amount);
        }

        // Création des transactions pour le sender et le receiver
        $this->economyService->createTransaction(TransactionType::DONATION, $currency, $amount, $sender, $receiver);
        $this->economyService->createTransaction(TransactionType::RECEIVE, $currency, $amount, $receiver, $sender);

        return $this->json([
            'success' => true,
            'balance' => [
                'discordId' => $sender->getDiscordId(),
                'gems'   => $sender->getGems(),
                'rubies' => $sender->getRubies(),
            ],
        ]);
    }

    #[Route('/add', name: 'app_bot_add', methods: ['POST'])]
    public function add(Request $request, RequestPayloadService $payloadService): JsonResponse
    {   
        // Extraction et validation des données JSON envoyées par le bot
        $payload = $payloadService->extractValidatedPayload($request, ['discordId', 'currency', 'amount']);
        if ($payload instanceof JsonResponse) return $payload;
        $userId = $payload['discordId'];
        $currency = $payload['currency'];
        $amount = $payload['amount'];

        // Vérifications de validité des données reçues
        if ($error = $this->economyService->validateTransactionData($currency, $amount)) {
            return $error; 
        }

        // Récupération de l'utilisateur cible via son identifiant Discord
        $user = $this->discordUserService->findOrCreateUserByDiscordId($userId);

        // Mise à jour du solde en fonction de la monnaie spécifiée
        if ($currency === 'gems') {
            $user->setGems($user->getGems() + $amount);
        } else {
            $user->setRubies($user->getRubies() + $amount);
        }

        // Création de la transaction 
        $this->economyService->createTransaction(TransactionType::ADMIN, $currency, $amount, $user);

        return $this->json([
            'success' => true,
            'balance' => [
                'discordId' => $user->getDiscordId(),
                'gems'   => $user->getGems(),
                'rubies' => $user->getRubies(),
            ],
        ]);
    }

    #[Route('/remove', name: 'app_bot_remove', methods: ['POST'])]
    public function remove(Request $request, RequestPayloadService $payloadService): JsonResponse
    {
        // Extraction et validation des données JSON envoyées par le bot
        $payload = $payloadService->extractValidatedPayload($request, ['discordId', 'currency', 'amount']);
        if ($payload instanceof JsonResponse) return $payload;
        $userId = $payload['discordId'];
        $currency = $payload['currency'];
        $amount = $payload['amount'];

        // Vérifications de validité des données reçues
        if ($error = $this->economyService->validateTransactionData($currency, $amount)) {
            return $error; 
        }

        // Récupération de l'utilisateur cible via son identifiant Discord
        $user = $this->discordUserService->findOrCreateUserByDiscordId($userId);

        // Mise à jour du solde en fonction de la monnaie spécifiée
        if ($currency === 'gems') {
            $user->setGems($user->getGems() - $amount);
        } else {
            $user->setRubies($user->getRubies() - $amount);
        }

        // Création de la transaction 
        $this->economyService->createTransaction(TransactionType::ADMIN, $currency, -$amount, $user);

        return $this->json([
            'success' => true,
            'balance' => [
                'discordId' => $user->getDiscordId(),
                'gems'   => $user->getGems(),
                'rubies' => $user->getRubies(),
            ],
        ]);
    }
    
    #[Route('/set', name: 'app_bot_set', methods: ['POST'])]
    public function set(Request $request, RequestPayloadService $payloadService): JsonResponse
    {
        // Extraction et validation des données JSON envoyées par le bot
        $payload = $payloadService->extractValidatedPayload($request, ['discordId', 'currency', 'amount']);
        if ($payload instanceof JsonResponse) return $payload;
        $userId = $payload['discordId'];
        $currency = $payload['currency'];
        $amount = $payload['amount'];

        // Vérifications de validité des données reçues
        if ($error = $this->economyService->validateTransactionData($currency, $amount)) {
            return $error; 
        }

        // Récupération de l'utilisateur cible via son identifiant Discord
        $user = $this->discordUserService->findOrCreateUserByDiscordId($userId);

        // Mise à jour du solde en fonction de la monnaie spécifiée
        if ($currency === 'gems') {
            $user->setGems($amount);
        } else {
            $user->setRubies($amount);
        }

        // Création de la transaction 
        $this->economyService->createTransaction(TransactionType::SET, $currency, $amount, $user);

        return $this->json([
            'success' => true,
            'balance' => [
                'discordId' => $user->getDiscordId(),
                'gems'   => $user->getGems(),
                'rubies' => $user->getRubies(),
            ],
        ]);
    }
}
