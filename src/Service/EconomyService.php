<?php

namespace App\Service;

use App\Entity\Item;
use App\Entity\User;
use App\Entity\Transaction;
use App\Enum\Currency;
use App\Enum\TransactionType;
use App\Exception\EconomyException;
use App\Repository\ConversionRateRepository;
use App\Repository\ItemRepository;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class EconomyService
{
    private const DEFAULT_PAGE_LIMIT = 10;

    public function __construct(
        private EntityManagerInterface $em,
        private TransactionRepository $transactionRepository,
        private ConversionRateRepository $conversionRateRepository,
        private ItemRepository $itemRepository
    ) {}

    /**
     * Vérifie les données d'une transaction avant traitement.
     */
    public function validateTransactionData(string $currency, int $amount, ?bool $isSet = false): void
    {
        if (!in_array($currency, ['gems', 'rubies'], true)) {
            throw new EconomyException('Monnaie invalide.', Response::HTTP_BAD_REQUEST);
        }

        $minAllowed = $isSet ? 0 : 1;
        if ($amount < $minAllowed) {
            $message = $isSet 
                ? 'Le montant doit être égal ou supérieur à 0.' 
                : 'Le montant doit être un nombre positif.';
                
            throw new EconomyException($message, Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Crée et enregistre une transaction.
     */
    public function createTransaction(TransactionType $type, string $currency, float $amount, User $owner, ?User $relatedUser = null, ?string $description = null
    ): Transaction {
        $transaction = new Transaction();
        $transaction
            ->setType($type)
            ->setCurrency(Currency::from($currency))
            ->setAmount($amount)
            ->setOwner($owner)
            ->setRelatedUser($relatedUser)
            ->setDescription($description);

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
        $totalCount = $this->transactionRepository->count($criteria);

        return [
            'items' => $this->formatTransactions($transactions),
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => ceil($totalCount / $limit),
                'totalItems' => $totalCount
            ]
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

    public function convertGemsToRubies(User $user, int $amount): array
    {
        if ($user->getGems() < $amount) {
            throw new EconomyException(
                'Vous n\'avez pas assez de gemmes.',
                Response::HTTP_BAD_REQUEST
            );
        }

        // Récupérer rang social 
        $data = $this->getUserHighestSocialRankData($user);

        $socialRankLevel = $data['rank'];
        $socialRoleId = $data['roleId'];

        // Calcul du taux
        $rate = $this->getConversionRateForRank($socialRankLevel);

        $rubiesEarned = $amount * $rate;

        // Sauvegarde ancien solde
        $oldGems = $user->getGems();
        $oldRubies = $user->getRubies();

        // Mise à jour soldes
        $user->setGems($oldGems - $amount);
        $user->setRubies($oldRubies + $rubiesEarned);

        $this->em->flush();

        // Transaction
        $this->createTransaction(
            TransactionType::CONVERT,
            'gems',
            -$amount,
            $user,
        );

        $this->createTransaction(
            TransactionType::CONVERT,
            'rubies',
            $rubiesEarned,
            $user,
        );

        return [
            'roleId' => $socialRoleId,
            'rate' => $rate,
            'converted' => $amount,
            'rubiesEarned' => $rubiesEarned,
            'previous' => [
                'gems' => $oldGems,
                'rubies' => $oldRubies
            ],
            'current' => [
                'gems' => $user->getGems(),
                'rubies' => $user->getRubies()
            ]
        ];
    }

    private function getUserHighestSocialRankData(User $user): array
    {
        $highestRank = null;
        $highestRoleId = null;

        foreach ($user->getInventories() as $inventory) {

            $item = $inventory->getItem();

            if ($item->getSocialRankLevel() === null || $inventory->getQuantity() <= 0) {
                continue;
            }

            $rank = $item->getSocialRankLevel();

            if ($highestRank === null || $rank > $highestRank) {
                $highestRank = $rank;
                $highestRoleId = $item->getDiscordRoleId();
            }
        }

        return [
            'rank' => $highestRank,
            'roleId' => $highestRoleId
        ];
    }

    private function getConversionRateForRank(?int $rank): float
    {
        if ($rank === null) {
            return 5;
        }

        $rate = $this->conversionRateRepository->findBestRateForRank($rank);

        return $rate?->getGemToRubyRate() ?? 5;
    }

    public function getConversionRatesOverview(User $user): array
    {
        // Récupération du plus haut rang du joueur 
        $rankData = $this->getUserHighestSocialRankData($user);
        $highestRank = $rankData['rank'];

        // Tous les taux
        $rateEntities = $this->conversionRateRepository->findBy(
            [],
            ['socialRankLevel' => 'ASC']
        );

        // Tous les items qui sont des rangs sociaux
        $rankItems = $this->itemRepository->findAllSocialRanks();

        // Mapping rankLevel => roleId
        $rankRoleMap = [];
        foreach ($rankItems as $item) {
            $rankRoleMap[$item->getSocialRankLevel()] = $item->getDiscordRoleId();
        }

        $rates = [];
        $currentRoleId = null;
        $currentRate = null;

        foreach ($rateEntities as $rate) {

            $rankLevel = $rate->getSocialRankLevel();
            $rateValue = $rate->getGemToRubyRate();
            $roleId = $rankRoleMap[$rankLevel] ?? null;

            $isCurrent = $highestRank !== null && $rankLevel === $highestRank;

            if ($isCurrent) {
                $currentRoleId = $roleId;
                $currentRate = $rateValue;
            }

            $rates[] = [
                'roleId' => $roleId,
                'rate' => $rateValue,
                'isCurrent' => $isCurrent,
            ];
        }

        return [
            'currentRoleId' => $currentRoleId,
            'currentRate'   => $currentRate,
            'rates' => $rates,
        ];
    }

    /**
     * Récupère le classement des joueurs selon la monnaie spécifiée (paginé).
     */
    public function getLeaderboard(string $currency, int $page = 1, int $limit = self::DEFAULT_PAGE_LIMIT): array
    {
        $userRepo = $this->em->getRepository(User::class);

        // 1. On compte d'abord le total pour valider la page
        $countQb = $userRepo->createQueryBuilder('u')->select('count(u.id)');
        if ($currency === 'gems') {
            $countQb->where('u.gems > 0');
        } else {
            $countQb->where('u.rubies > 0');
        }
        
        $totalCount = (int) $countQb->getQuery()->getSingleScalarResult();
        $totalPages = (int) max(1, ceil($totalCount / $limit));

        // 2. Ajustement de la page : On s'assure de ne pas dépasser totalPages
        // Si page = 3 et totalPages = 2, $page devient 2.
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $limit;

        // 3. Requête principale avec l'offset corrigé
        $qb = $userRepo->createQueryBuilder('u');
        if ($currency === 'gems') {
            $qb->where('u.gems > 0')->orderBy('u.gems', 'DESC');
        } else {
            $qb->where('u.rubies > 0')->orderBy('u.rubies', 'DESC');
        }

        $qb->setMaxResults($limit)->setFirstResult($offset);

        /** @var User[] $users */
        $users = $qb->getQuery()->getResult();

        // 4. Formatage
        $formattedUsers = array_map(fn(User $u) => [
            'discordId' => $u->getDiscordId(),
            'gems'      => $u->getGems(),
            'rubies'    => $u->getRubies(),
        ], $users);

        return [
            'users' => $formattedUsers,
            'pagination' => [
                'currentPage' => $page, // On renvoie la page ajustée
                'totalPages'  => $totalPages,
                'totalItems'  => $totalCount
            ]
        ];
    }

    public function getRoleMultiplier(User $user): float
    {
        $data = $this->getUserHighestSocialRankData($user);
        $rank = $data['rank'];

        if ($rank === null) {
            return 1;
        }

        // 🔥 Scaling simple basé sur le rang
        return match (true) {
            $rank >= 10 => 2.5,
            $rank >= 7  => 2.0,
            $rank >= 5  => 1.6,
            $rank >= 3  => 1.3,
            default     => 1.1,
        };
    }

    public function claimDaily(User $user): array
    {
        $cooldownRepo = $this->em->getRepository(\App\Entity\UserCooldown::class);

        $cooldown = $cooldownRepo->findOneBy([
            'user' => $user,
            'activity' => 'daily'
        ]);

        $tzParis = new \DateTimeZone('Europe/Paris');
        $tzUTC = new \DateTimeZone('UTC');

        $nowUTC = new \DateTimeImmutable('now', $tzUTC);

        $nowParis = $nowUTC->setTimezone($tzParis);
        $todayParis = $nowParis->setTime(0, 0);

        if ($cooldown) {
            $lastParis = $cooldown->getLastUsedAt()->setTimezone($tzParis);
            $lastDay = $lastParis->setTime(0, 0);

            if ($lastDay == $todayParis) {
                $nextReset = (clone $todayParis)->modify('+1 day');
                $timestamp = $nextReset->getTimestamp();

                throw new EconomyException(
                    "Vous avez déjà récupéré votre récompense aujourd'hui.\nProchain daily <t:$timestamp:R>",
                    Response::HTTP_BAD_REQUEST
                );
            }
        } else {
            $cooldown = new \App\Entity\UserCooldown();
            $cooldown->setUser($user);
            $cooldown->setActivity('daily');
            $cooldown->setStreak(0);
        }

        $streak = 1;

        if ($cooldown->getLastUsedAt()) {
            $lastParis = $cooldown->getLastUsedAt()->setTimezone($tzParis);
            $lastDay = $lastParis->setTime(0, 0);

            $yesterday = $todayParis->modify('-1 day');

            if ($lastDay == $yesterday) {
                $streak = $cooldown->getStreak() + 1;
            }
        }

        $cooldown->setStreak($streak);
        $cooldown->setLastUsedAt($nowUTC); // 🔥 STOCKAGE UTC

        $roleMultiplier = $this->getRoleMultiplier($user);

        $base = 10;
        $streakBonus = min(2, 1 + ($streak * 0.05));

        $reward = (int) round($base * $roleMultiplier * $streakBonus);

        $oldRubies = $user->getRubies();
        $user->setRubies($oldRubies + $reward);

        $this->createTransaction(
            TransactionType::GAIN,
            'rubies',
            $reward,
            $user,
            null,
            "Récompense journalière"
        );

        $this->em->persist($cooldown);
        $this->em->flush();

        return [
            'reward' => $reward,
            'streak' => $streak,
            'previous' => $oldRubies,
            'current' => $user->getRubies()
        ];
    }
}
