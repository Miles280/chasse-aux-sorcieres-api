<?php

namespace App\Repository;

use App\Entity\Transaction;
use App\Entity\User;
use App\Enum\TransactionType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function findLastRubyDonation(User $user): ?Transaction
    {
        return $this->createQueryBuilder('t')
            ->where('t.owner = :user')
            ->andWhere('t.type = :type')
            ->andWhere('t.currency = :currency')
            ->setParameter('user', $user)
            ->setParameter('type', TransactionType::DONATION)
            ->setParameter('currency', 'rubies')
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getLastTradeBetweenUsers(User $userA, User $userB): ?Transaction
    {
        return $this->createQueryBuilder('t')
            ->andWhere('(t.owner = :a AND t.relatedUser = :b) OR (t.owner = :b AND t.relatedUser = :a)')
            ->setParameter('a', $userA)
            ->setParameter('b', $userB)
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

//    /**
//     * @return Transaction[] Returns an array of Transaction objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Transaction
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
