<?php

namespace App\Command;

use App\Entity\Category;
use App\Entity\RecurringTransaction;
use App\Entity\Transaction;
use App\Entity\User;
use App\Repository\RecurringTransactionRepository;
use App\Service\BudgetExceededNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Generates real Transaction rows for every due RecurringTransaction. Meant
 * to be invoked periodically (see the "scheduler" service in
 * docker-compose.yml) - idempotent, so running it more often than needed is
 * harmless.
 */
#[AsCommand(name: 'app:generate-recurring-transactions', description: 'Generate transactions for due recurring transactions')]
class GenerateRecurringTransactionsCommand extends Command
{
    // Safety net against a corrupted nextRunDate stuck in the past (e.g.
    // an all-zero date) turning into an unbounded loop.
    private const MAX_OCCURRENCES_PER_RUN = 500;

    public function __construct(
        private readonly RecurringTransactionRepository $recurringTransactionRepository,
        private readonly EntityManagerInterface $em,
        private readonly BudgetExceededNotifier $budgetExceededNotifier,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('as-of', null, InputOption::VALUE_REQUIRED, 'Treat this date (Y-m-d) as "today" instead of the real current date, for backfills and tests');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $asOf = $input->getOption('as-of');
        $today = null !== $asOf ? new \DateTimeImmutable($asOf) : new \DateTimeImmutable('today');

        $due = $this->recurringTransactionRepository->findDue($today);
        $generated = 0;

        /** @var array<string, array{owner: User, category: Category, month: string, transactions: Transaction[]}> $groups */
        $groups = [];

        foreach ($due as $recurring) {
            $iterations = 0;
            while ($recurring->getNextRunDate() <= $today && $iterations < self::MAX_OCCURRENCES_PER_RUN) {
                $transaction = $this->buildTransaction($recurring);
                $month = $transaction->getDate()->format('Y-m');
                $key = sprintf('%d:%d:%s', $recurring->getOwner()->getId(), $recurring->getCategory()->getId(), $month);

                $groups[$key] ??= [
                    'owner' => $recurring->getOwner(),
                    'category' => $recurring->getCategory(),
                    'month' => $month,
                    'transactions' => [],
                ];
                $groups[$key]['transactions'][] = $transaction;

                $recurring->setNextRunDate($this->advance($recurring->getNextRunDate(), $recurring->getFrequency()));
                ++$generated;
                ++$iterations;
            }
        }

        // Grouped by owner/category/month (rather than one flush for
        // everything) so BudgetExceededNotifier sees an accurate before/after
        // total per group - the same budget-exceeded emails a manually
        // entered transaction would trigger also fire for recurring ones.
        foreach ($groups as $group) {
            $this->budgetExceededNotifier->checkAndNotify(
                $group['owner'],
                $group['category'],
                $group['month'],
                function () use ($group): void {
                    foreach ($group['transactions'] as $transaction) {
                        $this->em->persist($transaction);
                    }
                    $this->em->flush();
                }
            );
        }

        $io->writeln(sprintf('Generated %d transaction(s) from %d recurring transaction(s).', $generated, count($due)));

        return Command::SUCCESS;
    }

    private function buildTransaction(RecurringTransaction $recurring): Transaction
    {
        $transaction = new Transaction();
        $transaction->setOwner($recurring->getOwner());
        $transaction->setCategory($recurring->getCategory());
        $transaction->setAmount($recurring->getAmount());
        $transaction->setDescription($recurring->getDescription());
        $transaction->setDate($recurring->getNextRunDate());
        $transaction->setCreatedAt(new \DateTimeImmutable());

        return $transaction;
    }

    private function advance(\DateTimeImmutable $date, string $frequency): \DateTimeImmutable
    {
        if (RecurringTransaction::FREQUENCY_WEEKLY === $frequency) {
            return $date->modify('+1 week');
        }

        // Plain modify('+1 month') overflows for day-of-month values that
        // don't exist in the next month (Jan 31 -> Mar 3, not Feb 28/29), so
        // the day is clamped to the target month's last day instead.
        $targetDay = (int) $date->format('d');
        $firstOfNextMonth = $date->modify('first day of next month');
        $lastDayOfNextMonth = (int) $firstOfNextMonth->format('t');

        return $firstOfNextMonth->modify('+'.(min($targetDay, $lastDayOfNextMonth) - 1).' days');
    }
}
