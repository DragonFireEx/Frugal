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
     * Wraps a transaction write with a before/after budget check, sending an
     * email only on the false -> true edge (the category wasn't exceeded
     * before $mutate ran, but is exceeded after). $mutate is responsible for
     * persisting and flushing whatever write it wraps.
     */
    public function checkAndNotify(User $owner, Category $category, string $month, callable $mutate): void
    {
        $budget = $this->budgetRepository->findOneBy(['owner' => $owner, 'category' => $category]);
        if (!$budget) {
            $mutate();

            return;
        }

        $limit = (float) $budget->getMonthlyLimit();
        $wasExceeded = $this->totalSpent($owner, $category, $month) > $limit;

        $mutate();

        if ($wasExceeded) {
            return;
        }

        $total = $this->totalSpent($owner, $category, $month);
        if ($total <= $limit) {
            return;
        }

        $this->send($owner, $category, $month, $total, $limit);
    }

    private function totalSpent(User $owner, Category $category, string $month): float
    {
        return $this->transactionRepository->sumAmountForCategoryAndMonth($owner, $category, $month);
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
