<?php

namespace App\Service;

use App\Entity\Category;
use App\Entity\User;
use App\Repository\TransactionRepository;

class StatsCalculator
{
    public function __construct(private readonly TransactionRepository $transactionRepository)
    {
    }

    /**
     * @return array{month: string, income: string, expense: string, balance: string, byCategory: list<array{categoryId: int, categoryName: string, type: string, total: string}>}
     */
    public function calculateMonthly(User $owner, string $month): array
    {
        $transactions = $this->transactionRepository->findFiltered($owner, $month, null);

        $income = 0.0;
        $expense = 0.0;
        $byCategory = [];

        foreach ($transactions as $transaction) {
            $category = $transaction->getCategory();
            $amount = (float) $transaction->getAmount();

            if (Category::TYPE_INCOME === $category->getType()) {
                $income += $amount;
            } else {
                $expense += $amount;
            }

            $categoryId = $category->getId();
            if (!isset($byCategory[$categoryId])) {
                $byCategory[$categoryId] = [
                    'categoryId' => $categoryId,
                    'categoryName' => $category->getName(),
                    'type' => $category->getType(),
                    'total' => 0.0,
                ];
            }
            $byCategory[$categoryId]['total'] += $amount;
        }

        return [
            'month' => $month,
            'income' => number_format($income, 2, '.', ''),
            'expense' => number_format($expense, 2, '.', ''),
            'balance' => number_format($income - $expense, 2, '.', ''),
            'byCategory' => array_values(array_map(
                static fn (array $entry) => [
                    ...$entry,
                    'total' => number_format($entry['total'], 2, '.', ''),
                ],
                $byCategory
            )),
        ];
    }
}
