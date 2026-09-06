<?php

namespace App\Service;

use App\Entity\Category;
use App\Entity\User;
use App\Repository\BudgetRepository;
use App\Repository\TransactionRepository;

class StatsCalculator
{
    public function __construct(
        private readonly TransactionRepository $transactionRepository,
        private readonly BudgetRepository $budgetRepository,
    ) {
    }

    /**
     * @return array{month: string, income: string, expense: string, balance: string, byCategory: list<array{categoryId: int, categoryName: string, type: string, total: string, budgetLimit?: string, budgetExceeded?: bool}>}
     */
    public function calculateMonthly(User $owner, string $month): array
    {
        $transactions = $this->transactionRepository->findFiltered($owner, $month, null);

        $income = 0.0;
        $expense = 0.0;
        $byCategory = [];
        $categories = [];

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
                $categories[$categoryId] = $category;
            }
            $byCategory[$categoryId]['total'] += $amount;
        }

        foreach ($byCategory as $categoryId => &$entry) {
            $budget = $this->budgetRepository->findOneBy(['owner' => $owner, 'category' => $categories[$categoryId]]);
            if ($budget) {
                $limit = (float) $budget->getMonthlyLimit();
                $entry['budgetLimit'] = number_format($limit, 2, '.', '');
                $entry['budgetExceeded'] = $entry['total'] > $limit;
            }

            $entry['total'] = number_format($entry['total'], 2, '.', '');
        }
        unset($entry);

        return [
            'month' => $month,
            'income' => number_format($income, 2, '.', ''),
            'expense' => number_format($expense, 2, '.', ''),
            'balance' => number_format($income - $expense, 2, '.', ''),
            'byCategory' => array_values($byCategory),
        ];
    }

    /**
     * @return array{year: string, months: list<array{month: string, income: string, expense: string, balance: string}>}
     */
    public function calculateYearly(User $owner, string $year): array
    {
        $transactions = $this->transactionRepository->findByYear($owner, $year);

        $months = [];
        for ($i = 1; $i <= 12; ++$i) {
            $key = sprintf('%s-%02d', $year, $i);
            $months[$key] = ['month' => $key, 'income' => 0.0, 'expense' => 0.0];
        }

        foreach ($transactions as $transaction) {
            $key = $transaction->getDate()->format('Y-m');
            $amount = (float) $transaction->getAmount();

            if (Category::TYPE_INCOME === $transaction->getCategory()->getType()) {
                $months[$key]['income'] += $amount;
            } else {
                $months[$key]['expense'] += $amount;
            }
        }

        foreach ($months as &$entry) {
            $entry['balance'] = number_format($entry['income'] - $entry['expense'], 2, '.', '');
            $entry['income'] = number_format($entry['income'], 2, '.', '');
            $entry['expense'] = number_format($entry['expense'], 2, '.', '');
        }
        unset($entry);

        return [
            'year' => $year,
            'months' => array_values($months),
        ];
    }
}
