<?php

namespace App\Tests\Service;

use App\Entity\Budget;
use App\Entity\Category;
use App\Entity\Transaction;
use App\Entity\User;
use App\Repository\BudgetRepository;
use App\Repository\TransactionRepository;
use App\Service\BudgetExceededNotifier;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class BudgetExceededNotifierTest extends TestCase
{
    private User $owner;
    private Category $category;

    protected function setUp(): void
    {
        $this->owner = (new User())->setEmail('owner@example.com')->setName('Owner')->setPassword('hash');
        $this->category = (new Category())->setName('Groceries')->setType(Category::TYPE_EXPENSE);
    }

    private function transaction(string $amount): Transaction
    {
        $transaction = new Transaction();
        $transaction->setOwner($this->owner);
        $transaction->setCategory($this->category);
        $transaction->setAmount($amount);
        $transaction->setDate(new \DateTimeImmutable('2026-01-15'));
        $transaction->setCreatedAt(new \DateTimeImmutable());

        return $transaction;
    }

    private function budget(string $limit): Budget
    {
        return (new Budget())->setOwner($this->owner)->setCategory($this->category)->setMonthlyLimit($limit);
    }

    public function testIsExceededReturnsFalseWithoutABudget(): void
    {
        $budgetRepository = $this->createStub(BudgetRepository::class);
        $budgetRepository->method('findOneBy')->willReturn(null);

        $notifier = new BudgetExceededNotifier(
            $budgetRepository,
            $this->createStub(TransactionRepository::class),
            $this->createStub(MailerInterface::class),
            $this->createStub(LoggerInterface::class),
            'noreply@frugal.local'
        );

        self::assertFalse($notifier->isExceeded($this->owner, $this->category, '2026-01'));
    }

    public function testSendsAnEmailOnlyWhenTransitioningToExceeded(): void
    {
        $budgetRepository = $this->createStub(BudgetRepository::class);
        $budgetRepository->method('findOneBy')->willReturn($this->budget('100.00'));

        $transactionRepository = $this->createStub(TransactionRepository::class);
        $transactionRepository->method('findFiltered')->willReturn([$this->transaction('150.00')]);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (Email $email): bool {
                self::assertSame('owner@example.com', $email->getTo()[0]->getAddress());
                self::assertStringContainsString('Groceries', $email->getSubject());

                return true;
            }));

        $notifier = new BudgetExceededNotifier(
            $budgetRepository,
            $transactionRepository,
            $mailer,
            $this->createStub(LoggerInterface::class),
            'noreply@frugal.local'
        );

        $notifier->notifyIfNewlyExceeded($this->owner, $this->category, '2026-01', false);
    }

    public function testDoesNotSendWhenAlreadyExceededBefore(): void
    {
        $budgetRepository = $this->createMock(BudgetRepository::class);
        // Would be exceeded now too, but wasExceededBefore=true means it isn't "newly" exceeded.
        $budgetRepository->expects(self::never())->method('findOneBy');

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');

        $notifier = new BudgetExceededNotifier(
            $budgetRepository,
            $this->createStub(TransactionRepository::class),
            $mailer,
            $this->createStub(LoggerInterface::class),
            'noreply@frugal.local'
        );

        $notifier->notifyIfNewlyExceeded($this->owner, $this->category, '2026-01', true);
    }

    public function testDoesNotSendWhenStillUnderLimit(): void
    {
        $budgetRepository = $this->createStub(BudgetRepository::class);
        $budgetRepository->method('findOneBy')->willReturn($this->budget('100.00'));

        $transactionRepository = $this->createStub(TransactionRepository::class);
        $transactionRepository->method('findFiltered')->willReturn([$this->transaction('50.00')]);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');

        $notifier = new BudgetExceededNotifier(
            $budgetRepository,
            $transactionRepository,
            $mailer,
            $this->createStub(LoggerInterface::class),
            'noreply@frugal.local'
        );

        $notifier->notifyIfNewlyExceeded($this->owner, $this->category, '2026-01', false);
    }

    public function testLogsAndSwallowsMailerFailuresInsteadOfThrowing(): void
    {
        $budgetRepository = $this->createStub(BudgetRepository::class);
        $budgetRepository->method('findOneBy')->willReturn($this->budget('100.00'));

        $transactionRepository = $this->createStub(TransactionRepository::class);
        $transactionRepository->method('findFiltered')->willReturn([$this->transaction('150.00')]);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())->method('send')->willThrowException(new TransportException('SMTP unreachable'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error');

        $notifier = new BudgetExceededNotifier(
            $budgetRepository,
            $transactionRepository,
            $mailer,
            $logger,
            'noreply@frugal.local'
        );

        $notifier->notifyIfNewlyExceeded($this->owner, $this->category, '2026-01', false);
    }
}
