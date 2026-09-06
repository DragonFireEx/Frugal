<?php

namespace App\Service;

use App\Entity\Category;
use App\Entity\User;
use App\Repository\BudgetRepository;
use App\Repository\TransactionRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Emails the owner the first time a category's spending crosses its budget
 * for a given month. Sent synchronously (no queue): a personal finance app
 * generates at most a handful of these a day, so the ~hundreds of ms an SMTP
 * call adds to that one request isn't worth a Messenger worker.
 */
class BudgetExceededNotifier
{
    public function __construct(
        private readonly BudgetRepository $budgetRepository,
        private readonly TransactionRepository $transactionRepository,
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        private readonly string $fromAddress,
    ) {
    }

    /**
     * Call before writing the transaction that might push the category over
     * budget, to capture whether it was already exceeded beforehand.
     */
    public function isExceeded(User $owner, Category $category, string $month): bool
    {
        $budget = $this->budgetRepository->findOneBy(['owner' => $owner, 'category' => $category]);
        if (!$budget) {
            return false;
        }

        return $this->totalSpent($owner, $category, $month) > (float) $budget->getMonthlyLimit();
    }

    /**
     * Call after the write has been flushed. Sends an email only if the
     * category is exceeded now and wasn't before (the false -> true edge).
     */
    public function notifyIfNewlyExceeded(User $owner, Category $category, string $month, bool $wasExceededBefore): void
    {
        if ($wasExceededBefore) {
            return;
        }

        $budget = $this->budgetRepository->findOneBy(['owner' => $owner, 'category' => $category]);
        if (!$budget) {
            return;
        }

        $limit = (float) $budget->getMonthlyLimit();
        $total = $this->totalSpent($owner, $category, $month);

        if ($total <= $limit) {
            return;
        }

        $this->send($owner, $category, $month, $total, $limit);
    }

    private function totalSpent(User $owner, Category $category, string $month): float
    {
        $transactions = $this->transactionRepository->findFiltered($owner, $month, $category->getId());

        $total = 0.0;
        foreach ($transactions as $transaction) {
            $total += (float) $transaction->getAmount();
        }

        return $total;
    }

    private function send(User $owner, Category $category, string $month, float $total, float $limit): void
    {
        $email = (new Email())
            ->from($this->fromAddress)
            ->to($owner->getEmail())
            ->subject(sprintf('Przekroczono budżet: %s', $category->getName()))
            ->text(sprintf(
                "Przekroczono miesięczny budżet dla kategorii \"%s\" (%s).\n\nLimit: %.2f\nWydano: %.2f",
                $category->getName(),
                $month,
                $limit,
                $total
            ));

        try {
            $this->mailer->send($email);
        } catch (\Throwable $exception) {
            // A misconfigured/unreachable mail transport must not fail the
            // request that triggered it - the transaction is already saved.
            $this->logger->error('Failed to send budget-exceeded notification: {message}', [
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);
        }
    }
}
