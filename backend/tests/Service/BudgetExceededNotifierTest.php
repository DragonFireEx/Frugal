<?php

namespace App\Tests\Service;

use App\Entity\Budget;
use App\Entity\Category;
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

    private function budget(string $limit): Budget
    {
        return (new Budget())->setOwner($this->owner)->setCategory($this->category)->setMonthlyLimit($limit);
    }

    public function testRunsMutateWithoutQueryingSpendingWhenNoBudgetExists(): void
    {
        $budgetRepository = $this->createStub(BudgetRepository::class);
        $budgetRepository->method('findOneBy')->willReturn(null);

        $transactionRepository = $this->createMock(TransactionRepository::class);
        $transactionRepository->expects(self::never())->method('sumAmountForCategoryAndMonth');

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');

        $notifier = new BudgetExceededNotifier(
            $budgetRepository,
            $transactionRepository,
            $mailer,
            $this->createStub(LoggerInterface::class),
            'noreply@frugal.local'
        );

        $mutated = false;
        $notifier->checkAndNotify($this->owner, $this->category, '2026-01', function () use (&$mutated): void {
            $mutated = true;
        });

        self::assertTrue($mutated);
    }

    public function testSendsAnEmailOnlyWhenTransitioningToExceeded(): void
    {
        $budgetRepository = $this->createStub(BudgetRepository::class);
        $budgetRepository->method('findOneBy')->willReturn($this->budget('100.00'));

        $transactionRepository = $this->createStub(TransactionRepository::class);
        // Under the limit before $mutate runs, over it after.
        $transactionRepository->method('sumAmountForCategoryAndMonth')->willReturnOnConsecutiveCalls(50.0, 150.0);

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

        $notifier->checkAndNotify($this->owner, $this->category, '2026-01', static function (): void {
        });
    }

    public function testDoesNotSendWhenAlreadyExceededBefore(): void
    {
        $budgetRepository = $this->createStub(BudgetRepository::class);
        $budgetRepository->method('findOneBy')->willReturn($this->budget('100.00'));

        $transactionRepository = $this->createStub(TransactionRepository::class);
        // Already over the limit before $mutate runs too - not a "newly" exceeded edge.
        $transactionRepository->method('sumAmountForCategoryAndMonth')->willReturn(150.0);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');

        $notifier = new BudgetExceededNotifier(
            $budgetRepository,
            $transactionRepository,
            $mailer,
            $this->createStub(LoggerInterface::class),
            'noreply@frugal.local'
        );

        $notifier->checkAndNotify($this->owner, $this->category, '2026-01', static function (): void {
        });
    }

    public function testDoesNotSendWhenStillUnderLimit(): void
    {
        $budgetRepository = $this->createStub(BudgetRepository::class);
        $budgetRepository->method('findOneBy')->willReturn($this->budget('100.00'));

        $transactionRepository = $this->createStub(TransactionRepository::class);
        $transactionRepository->method('sumAmountForCategoryAndMonth')->willReturn(50.0);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');

        $notifier = new BudgetExceededNotifier(
            $budgetRepository,
            $transactionRepository,
            $mailer,
            $this->createStub(LoggerInterface::class),
            'noreply@frugal.local'
        );

        $notifier->checkAndNotify($this->owner, $this->category, '2026-01', static function (): void {
        });
    }

    public function testLogsAndSwallowsMailerFailuresInsteadOfThrowing(): void
    {
        $budgetRepository = $this->createStub(BudgetRepository::class);
        $budgetRepository->method('findOneBy')->willReturn($this->budget('100.00'));

        $transactionRepository = $this->createStub(TransactionRepository::class);
        $transactionRepository->method('sumAmountForCategoryAndMonth')->willReturnOnConsecutiveCalls(50.0, 150.0);

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

        $notifier->checkAndNotify($this->owner, $this->category, '2026-01', static function (): void {
        });
    }
}
