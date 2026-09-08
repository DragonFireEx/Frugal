<?php

namespace App\Repository;

use App\Entity\Category;
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
            ->addSelect('c')
            ->leftJoin('t.category', 'c')
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

    /**
     * @return Transaction[]
     */
    public function findByYear(User $owner, string $year): array
    {
        $start = new \DateTimeImmutable($year.'-01-01');
        $end = $start->modify('+1 year');

        return $this->createQueryBuilder('t')
            ->andWhere('t.owner = :owner')
            ->andWhere('t.date >= :start AND t.date < :end')
            ->setParameter('owner', $owner)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();
    }

    public function sumAmountForCategoryAndMonth(User $owner, Category $category, string $month): float
    {
        $start = new \DateTimeImmutable($month.'-01');
        $end = $start->modify('first day of next month');

        $sum = $this->createQueryBuilder('t')
            ->select('SUM(t.amount)')
            ->andWhere('t.owner = :owner')
            ->andWhere('t.category = :category')
            ->andWhere('t.date >= :start AND t.date < :end')
            ->setParameter('owner', $owner)
            ->setParameter('category', $category)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($sum ?? 0.0);
    }
}
