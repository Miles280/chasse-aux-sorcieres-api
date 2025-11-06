<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\TransactionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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


    #[Route('/balance/{discordId}', name: 'app_bot_balance', methods: ['GET'])]
    public function balance(string $discordId): JsonResponse
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
}
