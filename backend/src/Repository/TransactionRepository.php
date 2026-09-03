<?php

namespace App\Repository;

use App\Entity\Transaction;
use App\Entity\User;
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

    /**
     * @return Transaction[]
     */
    public function findFiltered(User $owner, ?string $month, ?int $categoryId): array
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('t.date', 'DESC');

        if (null !== $month) {
            $start = new \DateTimeImmutable($month.'-01');
            $end = $start->modify('first day of next month');

            $qb->andWhere('t.date >= :start AND t.date < :end')
                ->setParameter('start', $start)
                ->setParameter('end', $end);
        }

        if (null !== $categoryId) {
            $qb->andWhere('t.category = :categoryId')
                ->setParameter('categoryId', $categoryId);
        }

        return $qb->getQuery()->getResult();
    }
}
