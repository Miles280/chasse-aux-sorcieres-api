<?php

namespace App\Controller\Bot;

use App\Enum\TransactionType;
use App\Exception\EconomyException;
use App\Exception\InvalidPayloadException;
use App\Service\Auth\DiscordUserManager;
use App\Service\EconomyService;
use App\Service\RequestPayloadService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bot/economy')]
final class EconomyController extends AbstractBotController
{
    private EconomyService $economyService;
    private DiscordUserManager $discordUserService;

    public function __construct(EconomyService $economyService, DiscordUserManager $discordUserService)
    {
        $this->economyService = $economyService;
        $this->discordUserService = $discordUserService;
    }

    #[Route('/view/{discordId}', name: 'app_bot_economy_view', methods: ['GET'])]
    public function view(string $discordId): JsonResponse
    {
        try { 
            // Récupération du user
            $user = $this->discordUserService->findOrCreateUserByDiscordId($discordId);

            // Délégation complète au service
            $overview = $this->economyService->getUserOverview($user);

            return $this->successResponse($overview);
            
        } catch (\Throwable $e) {
            return $this->errorResponse("Erreur interne du serveur.", Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/give', name: 'app_bot_economy_give', methods: ['POST'])]
    public function give(Request $request, RequestPayloadService $payloadService): JsonResponse
    {
        try {
            // Extraction et validation des données JSON envoyées par le bot
            $payload = $payloadService->extractValidatedPayload($request, ['senderId', 'receiverId', 'currency', 'amount']);
            
            $senderId = $payload['senderId'];
            $receiverId = $payload['receiverId'];
            $currency = $payload['currency'];
            $amount = $payload['amount'];

            // Vérifications de validité des données reçues
            $this->economyService->validateTransactionData($currency, $amount);
            
            if ($senderId === $receiverId) {
                throw new InvalidPayloadException('Un utilisateur ne peut pas se donner de monnaie à lui-même.', Response::HTTP_BAD_REQUEST);
            }

            // Récupération des utilisateurs expéditeur et destinataire
            $sender = $this->discordUserService->findOrCreateUserByDiscordId($senderId);
            $receiver = $this->discordUserService->findOrCreateUserByDiscordId($receiverId);

            // Vérification du solde de l’expéditeur
            $senderBalance = $currency === 'gems' ? $sender->getGems() : $sender->getRubies();
            if ($senderBalance < $amount) {
                throw new EconomyException('Solde insuffisant.', Response::HTTP_BAD_REQUEST);
            }

            // Vérification du cooldown uniquement si la monnaie est "rubies"
            if ($currency === 'rubies') {

                $lastDonation = $this->economyService->getLastRubyDonation($sender);

                if ($lastDonation !== null) {
                    $lastDate = $lastDonation->getCreatedAt();
                    $nextPossible = (clone $lastDate)->modify('+48 hours');

                    if (new \DateTime() < $nextPossible) {
                        $timestamp = $nextPossible->getTimestamp();

                        throw new EconomyException("Vous avez déjà donné des Rubis récemment.\n Vous pourrez en donner de nouveau <t:$timestamp:R> (à <t:$timestamp:t>).", Response::HTTP_BAD_REQUEST);
                    }
                }
            }

            // Stockage du solde de l'utilisateur avant modification
            $oldGems = $sender->getGems();
            $oldRubies = $sender->getRubies();

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

            return $this->successResponse([
                'previous' => [
                    'gems'   => $oldGems,
                    'rubies' => $oldRubies
                ],
                'current' => [
                    'gems'   => $sender->getGems(),
                    'rubies' => $sender->getRubies(),
                ]
            ]);

        } catch (InvalidPayloadException $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (EconomyException $e) {
            $statusCode = $e->getCode() ?: Response::HTTP_BAD_REQUEST;
            return $this->errorResponse($e->getMessage(), $statusCode);
        } catch (\Throwable $e) {
            return $this->errorResponse("Erreur interne du serveur.", Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/add', name: 'app_bot_economy_add', methods: ['POST'])]
    public function add(Request $request, RequestPayloadService $payloadService): JsonResponse
    {
        try {   
            // Extraction et validation des données JSON envoyées par le bot
            $payload = $payloadService->extractValidatedPayload($request, ['discordId', 'currency', 'amount']);
            
            $userId = $payload['discordId'];
            $currency = $payload['currency'];
            $amount = $payload['amount'];

            // Vérifications de validité des données reçues
            $this->economyService->validateTransactionData($currency, $amount);

            // Récupération de l'utilisateur cible via son identifiant Discord
            $user = $this->discordUserService->findOrCreateUserByDiscordId($userId);

            // Stockage du solde de l'utilisateur avant modification
            $oldGems = $user->getGems();
            $oldRubies = $user->getRubies();

            // Mise à jour du solde en fonction de la monnaie spécifiée
            if ($currency === 'gems') {
                $user->setGems($oldGems + $amount);
            } else {
                $user->setRubies($oldRubies + $amount);
            }

            // Création de la transaction 
            $this->economyService->createTransaction(TransactionType::ADD, $currency, $amount, $user);

            return $this->successResponse([
                'previous' => [
                    'gems'   => $oldGems,
                    'rubies' => $oldRubies
                ],
                'current' => [
                    'gems'   => $user->getGems(),
                    'rubies' => $user->getRubies(),
                ]
            ]);

        } catch (InvalidPayloadException $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (EconomyException $e) {
            $statusCode = $e->getCode() ?: Response::HTTP_BAD_REQUEST;
            return $this->errorResponse($e->getMessage(), $statusCode);
        } catch (\Throwable $e) {
            return $this->errorResponse("Erreur interne du serveur.", Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/remove', name: 'app_bot_economy_remove', methods: ['POST'])]
    public function remove(Request $request, RequestPayloadService $payloadService): JsonResponse
    {
        try { 
            // Extraction et validation des données JSON envoyées par le bot
            $payload = $payloadService->extractValidatedPayload($request, ['discordId', 'currency', 'amount']);
            
            $userId = $payload['discordId'];
            $currency = $payload['currency'];
            $amount = $payload['amount'];

            // Vérifications de validité des données reçues
            $this->economyService->validateTransactionData($currency, $amount);

            // Récupération de l'utilisateur cible via son identifiant Discord
            $user = $this->discordUserService->findOrCreateUserByDiscordId($userId);

            // Stockage du solde de l'utilisateur avant modification
            $oldGems = $user->getGems();
            $oldRubies = $user->getRubies();

            // Mise à jour du solde en fonction de la monnaie spécifiée
            if ($currency === 'gems') {
                if ($oldGems < $amount) {
                    throw new EconomyException("Le membre spécifié n'a pas assez de Gemmes pour cette opération.", Response::HTTP_BAD_REQUEST);
                }
                $user->setGems($oldGems - $amount);
            } else {
                if ($oldRubies < $amount) {
                    throw new EconomyException("Le membre spécifié n'a pas assez de Rubis pour cette opération.", Response::HTTP_BAD_REQUEST);
                }
                $user->setRubies($oldRubies - $amount);
            }

            // Création de la transaction 
            $this->economyService->createTransaction(TransactionType::REMOVE, $currency, $amount, $user);

            return $this->successResponse([
                'previous' => [
                    'gems'   => $oldGems,
                    'rubies' => $oldRubies
                ],
                'current' => [
                    'gems'   => $user->getGems(),
                    'rubies' => $user->getRubies(),
                ]
            ]);

        } catch (InvalidPayloadException $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (EconomyException $e) {
            $statusCode = $e->getCode() ?: Response::HTTP_BAD_REQUEST;
            return $this->errorResponse($e->getMessage(), $statusCode);
        } catch (\Throwable $e) {
            return $this->errorResponse("Erreur interne du serveur.", Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    #[Route('/set', name: 'app_bot_economy_set', methods: ['POST'])]
    public function set(Request $request, RequestPayloadService $payloadService): JsonResponse
    {
        try { 
            // Extraction et validation des données JSON envoyées par le bot
            $payload = $payloadService->extractValidatedPayload($request, ['discordId', 'currency', 'amount']);
            
            $userId = $payload['discordId'];
            $currency = $payload['currency'];
            $amount = $payload['amount'];

            // Vérifications de validité des données reçues
            $this->economyService->validateTransactionData($currency, $amount, true);

            // Récupération de l'utilisateur cible via son identifiant Discord
            $user = $this->discordUserService->findOrCreateUserByDiscordId($userId);

            // Stockage du solde de l'utilisateur avant modification
            $oldGems = $user->getGems();
            $oldRubies = $user->getRubies();

            // Mise à jour du solde en fonction de la monnaie spécifiée
            if ($currency === 'gems') {
                $user->setGems($amount);
            } else {
                $user->setRubies($amount);
            }

            // Création de la transaction 
            $this->economyService->createTransaction(TransactionType::SET, $currency, $amount, $user);

            return $this->successResponse([
                'previous' => [
                    'gems'   => $oldGems,
                    'rubies' => $oldRubies
                ],
                'current' => [
                    'gems'   => $user->getGems(),
                    'rubies' => $user->getRubies(),
                ]
            ]);

        } catch (InvalidPayloadException $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (EconomyException $e) {
            $statusCode = $e->getCode() ?: Response::HTTP_BAD_REQUEST;
            return $this->errorResponse($e->getMessage(), $statusCode);
        } catch (\Throwable $e) {
            return $this->errorResponse("Erreur interne du serveur.", Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/transactions/{discordId}', name: 'app_bot_economy_transactions', methods: ['GET'])]
    public function history(string $discordId, Request $request): JsonResponse
    {
        try { 
            // Récupération de l'utilisateur
            $user = $this->discordUserService->findOrCreateUserByDiscordId($discordId);

            // Récupération du numéro de page et des filtres de type
            $page = max(1, (int) $request->query->get('page', 1));
            $types = $request->query->get('types', ''); 
            $types = $types ? explode(',', $types) : [];

            // Appel du service pour récupérer les transactions formatées
            $history = $this->economyService->getTransactionHistory($user, $page, $types);

            return $this->successResponse($history);

        } catch (\Throwable $e) {
            return $this->errorResponse("Erreur interne du serveur.", Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/convert', name: 'app_bot_economy_convert', methods: ['POST'])]
    public function convert(Request $request, RequestPayloadService $payloadService): JsonResponse
    {
        try {
            // Extraction et validation des données JSON envoyées par le bot
            $payload = $payloadService->extractValidatedPayload(
                $request,
                ['discordId', 'amount']
            );

            $discordId = $payload['discordId'];
            $amount = (int) $payload['amount'];

            if ($amount <= 0) {
                throw new EconomyException(
                    'Le montant doit être supérieur à 0.',
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Récupération de l'utilisateur
            $user = $this->discordUserService->findOrCreateUserByDiscordId($discordId);

            // Délégation complète au service
            $result = $this->economyService->convertGemsToRubies($user, $amount);

            return $this->successResponse($result);

        } catch (InvalidPayloadException $e) {
            return $this->errorResponse(
                $e->getMessage(),
                Response::HTTP_BAD_REQUEST
            );
        } catch (EconomyException $e) {
            $statusCode = $e->getCode() ?: Response::HTTP_BAD_REQUEST;

            return $this->errorResponse(
                $e->getMessage(),
                $statusCode
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                "Erreur interne du serveur.",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/rates/{discordId}', name: 'app_bot_economy_rates', methods: ['GET'])]
    public function rates(string $discordId): JsonResponse
    {
        try {
            $user = $this->discordUserService->findOrCreateUserByDiscordId($discordId);

            $overview = $this->economyService->getConversionRatesOverview($user);

            return $this->successResponse($overview);

        } catch (\Throwable $e) {
            return $this->errorResponse(
                "Erreur interne du serveur.",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/leaderboard', name: 'app_bot_economy_leaderboard', methods: ['GET'])]
    public function leaderboard(Request $request): JsonResponse
    {
        try {
            // Récupération des paramètres envoyés par le bot
            $page = max(1, (int) $request->query->get('page', 1));
            $currency = $request->query->get('currency', 'gems'); // Par défaut on affiche les gemmes

            // Vérification de la monnaie
            if (!in_array($currency, ['gems', 'rubies'], true)) {
                throw new EconomyException("Monnaie invalide pour le classement.", Response::HTTP_BAD_REQUEST);
            }

            // Appel au service pour récupérer le classement
            $leaderboard = $this->economyService->getLeaderboard($currency, $page);

            return $this->successResponse($leaderboard);

        } catch (EconomyException $e) {
            $statusCode = $e->getCode() ?: Response::HTTP_BAD_REQUEST;
            return $this->errorResponse($e->getMessage(), $statusCode);
        } catch (\Throwable $e) {
            return $this->errorResponse("Erreur interne du serveur.", Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
