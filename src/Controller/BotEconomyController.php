<?php

namespace App\Controller;

use App\Enum\TransactionType;
use App\Service\Auth\DiscordUserManager;
use App\Service\EconomyService;
use App\Service\RequestPayloadService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bot/economy')]
final class BotEconomyController extends AbstractController
{
    private EconomyService $economyService;
    private DiscordUserManager $discordUserService;

    public function __construct(EconomyService $economyService, DiscordUserManager $discordUserService)
    {
        $this->economyService = $economyService;
        $this->discordUserService = $discordUserService;
    }

    #[Route('/{discordId}', name: 'app_bot_economy_view', methods: ['GET'])]
    public function view(string $discordId): JsonResponse
    {
        // Récupération du user
        $user = $this->discordUserService->findOrCreateUserByDiscordId($discordId);

        // Délégation complète au service
        $overview = $this->economyService->getUserOverview($user);

        return $this->json($overview);
    }

    #[Route('/give', name: 'app_bot_economy_give', methods: ['POST'])]
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

        // Vérification du cooldown uniquement si la monnaie est "rubies"
        if ($currency === 'rubies') {

            $lastDonation = $this->economyService->getLastRubyDonation($sender);

            if ($lastDonation !== null) {
                $lastDate = $lastDonation->getCreatedAt();
                $nextPossible = (clone $lastDate)->modify('+48 hours');

                if (new \DateTime() < $nextPossible) {
                    $timestamp = $nextPossible->getTimestamp();

                    return $this->json([
                        'error' => "Vous avez déjà donné des Rubis récemment.\n Vous pourrez en donner de nouveau <t:$timestamp:R> (à <t:$timestamp:t>)."
                    ], 400);
                }
            }
        }

        // Mise à jour des soldes en fonction de la monnaie spécifiée
        if ($currency === 'gems') {
            $old = $sender->getGems();
            $sender->setGems($sender->getGems() - $amount);
            $receiver->setGems($receiver->getGems() + $amount);
        } else {
            $old = $sender->getRubies();
            $sender->setRubies($sender->getRubies() - $amount);
            $receiver->setRubies($receiver->getRubies() + $amount);
        }

        // Création des transactions pour le sender et le receiver
        $this->economyService->createTransaction(TransactionType::DONATION, $currency, $amount, $sender, $receiver);
        $this->economyService->createTransaction(TransactionType::RECEIVE, $currency, $amount, $receiver, $sender);

        return $this->json([
            'success' => true,
            'old' => $old,
            'balance' => [
                'gems'   => $sender->getGems(),
                'rubies' => $sender->getRubies(),
            ],
        ]);
    }

    #[Route('/add', name: 'app_bot_economy_add', methods: ['POST'])]
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
            $old = $user->getGems();
            $user->setGems($old + $amount);
        } else {
            $old = $user->getRubies();
            $user->setRubies($old + $amount);
        }

        // Création de la transaction 
        $this->economyService->createTransaction(TransactionType::ADD, $currency, $amount, $user);

        return $this->json([
            'success' => true,
            'old' => $old,
            'balance' => [
                'gems'   => $user->getGems(),
                'rubies' => $user->getRubies(),
            ],
        ]);
    }

    #[Route('/remove', name: 'app_bot_economy_remove', methods: ['POST'])]
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
            $old = $user->getGems();

            if ($old < $amount) {
                return $this->json([
                    'success' => false,
                    'error' => "Le membre spécifié n'a pas assez de Gemmes pour cette opération."
                ], 400);
            }

            $user->setGems($old - $amount);

        } else {
            $old = $user->getRubies();

            if ($old < $amount) {
                return $this->json([
                    'success' => false,
                    'error' => "Le membre spécifié n'a pas assez de Rubis pour cette opération."
                ], 400);
            }

            $user->setRubies($old - $amount);
        }

        // Création de la transaction 
        $this->economyService->createTransaction(TransactionType::REMOVE, $currency, $amount, $user);

        return $this->json([
            'success' => true,
            'old' => $old,
            'balance' => [
                'gems'   => $user->getGems(),
                'rubies' => $user->getRubies(),
            ],
        ]);
    }
    
    #[Route('/set', name: 'app_bot_economy_set', methods: ['POST'])]
    public function set(Request $request, RequestPayloadService $payloadService): JsonResponse
    {
        // Extraction et validation des données JSON envoyées par le bot
        $payload = $payloadService->extractValidatedPayload($request, ['discordId', 'currency', 'amount']);
        if ($payload instanceof JsonResponse) return $payload;
        $userId = $payload['discordId'];
        $currency = $payload['currency'];
        $amount = $payload['amount'];

        // Vérifications de validité des données reçues
        if ($error = $this->economyService->validateTransactionData($currency, $amount, true)) {
            return $error; 
        }

        // Récupération de l'utilisateur cible via son identifiant Discord
        $user = $this->discordUserService->findOrCreateUserByDiscordId($userId);

        // Mise à jour du solde en fonction de la monnaie spécifiée
        if ($currency === 'gems') {
            $old = $user->getGems();
            $user->setGems($amount);
        } else {
            $old = $user->getRubies();
            $user->setRubies($amount);
        }

        // Création de la transaction 
        $this->economyService->createTransaction(TransactionType::SET, $currency, $amount, $user);

        return $this->json([
            'success' => true,
            'old' => $old,
            'balance' => [
                'gems'   => $user->getGems(),
                'rubies' => $user->getRubies(),
            ],
        ]);
    }

    #[Route('/transactions/{discordId}', name: 'app_bot_economy_transactions', methods: ['GET'])]
    public function history(string $discordId, Request $request): JsonResponse
    {
        // Récupération de l'utilisateur
        $user = $this->discordUserService->findOrCreateUserByDiscordId($discordId);

        // Récupération du numéro de page et des filtres de type
        $page = max(1, (int) $request->query->get('page', 1));
        $types = $request->query->get('types', ''); 
        $types = $types ? explode(',', $types) : [];

        // Appel du service pour récupérer les transactions formatées
        $history = $this->economyService->getTransactionHistory($user, $page, $types);

        return $this->json($history);
    }

}
