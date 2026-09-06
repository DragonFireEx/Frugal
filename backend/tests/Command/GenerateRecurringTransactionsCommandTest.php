<?php

namespace App\Tests\Command;

use App\Tests\Controller\ApiTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class GenerateRecurringTransactionsCommandTest extends ApiTestCase
{
    private function invokeGenerateCommand(object $kernel, string $asOf): void
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);
        $commandTester = new CommandTester($application->find('app:generate-recurring-transactions'));
        $commandTester->execute(['--as-of' => $asOf]);
    }

    public function testGeneratesTransactionAndClampsMonthlyDayOverflow(): void
    {
        $client = static::createClient();
        $token = $this->registerAndLogin($client, 'recurring');

        $client->request('POST', '/api/categories', server: $this->jsonHeaders($token), content: json_encode([
            'name' => 'Czynsz',
            'type' => 'expense',
        ]));
        self::assertResponseStatusCodeSame(201);
        $category = json_decode($client->getResponse()->getContent(), true);

        $client->request('POST', '/api/recurring-transactions', server: $this->jsonHeaders($token), content: json_encode([
            'categoryId' => $category['id'],
            'amount' => '1500.00',
            'description' => 'Czynsz za mieszkanie',
            'frequency' => 'monthly',
            'nextRunDate' => '2026-01-31',
        ]));
        self::assertResponseStatusCodeSame(201);

        // As of Jan 31 itself, the occurrence is due today.
        $this->invokeGenerateCommand($client->getKernel(), '2026-01-31');

        $client->request('GET', '/api/transactions?month=2026-01', server: $this->authHeaders($token));
        self::assertResponseIsSuccessful();
        $transactions = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(1, $transactions);
        self::assertSame('2026-01-31', $transactions[0]['date']);
        self::assertSame('1500.00', $transactions[0]['amount']);
        self::assertSame('Czynsz za mieszkanie', $transactions[0]['description']);

        $client->request('GET', '/api/recurring-transactions', server: $this->authHeaders($token));
        $recurring = json_decode($client->getResponse()->getContent(), true)[0];
        // Jan 31 + 1 month must clamp to Feb 28 (2026 isn't a leap year), not overflow to Mar 3.
        self::assertSame('2026-02-28', $recurring['nextRunDate']);

        // Running again the same day must not duplicate the transaction.
        $this->invokeGenerateCommand($client->getKernel(), '2026-01-31');
        $client->request('GET', '/api/transactions?month=2026-01', server: $this->authHeaders($token));
        self::assertCount(1, json_decode($client->getResponse()->getContent(), true));
    }

    public function testCatchesUpMultipleMissedWeeklyOccurrences(): void
    {
        $client = static::createClient();
        $token = $this->registerAndLogin($client, 'recurring-weekly');

        $client->request('POST', '/api/categories', server: $this->jsonHeaders($token), content: json_encode([
            'name' => 'Subskrypcja',
            'type' => 'expense',
        ]));
        self::assertResponseStatusCodeSame(201);
        $category = json_decode($client->getResponse()->getContent(), true);

        $client->request('POST', '/api/recurring-transactions', server: $this->jsonHeaders($token), content: json_encode([
            'categoryId' => $category['id'],
            'amount' => '20.00',
            'frequency' => 'weekly',
            'nextRunDate' => '2026-01-05',
        ]));
        self::assertResponseStatusCodeSame(201);

        // Three weekly occurrences fall on or before Jan 19 (5th, 12th, 19th).
        $this->invokeGenerateCommand($client->getKernel(), '2026-01-19');

        $client->request('GET', '/api/transactions', server: $this->authHeaders($token));
        self::assertResponseIsSuccessful();
        $transactions = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(3, $transactions);

        $client->request('GET', '/api/recurring-transactions', server: $this->authHeaders($token));
        $recurring = json_decode($client->getResponse()->getContent(), true)[0];
        self::assertSame('2026-01-26', $recurring['nextRunDate']);
    }

    public function testDoesNotGenerateForInactiveRecurringTransaction(): void
    {
        $client = static::createClient();
        $token = $this->registerAndLogin($client, 'recurring-inactive');

        $client->request('POST', '/api/categories', server: $this->jsonHeaders($token), content: json_encode([
            'name' => 'Czynsz',
            'type' => 'expense',
        ]));
        self::assertResponseStatusCodeSame(201);
        $category = json_decode($client->getResponse()->getContent(), true);

        $client->request('POST', '/api/recurring-transactions', server: $this->jsonHeaders($token), content: json_encode([
            'categoryId' => $category['id'],
            'amount' => '1500.00',
            'frequency' => 'monthly',
            'nextRunDate' => '2026-01-01',
            'active' => false,
        ]));
        self::assertResponseStatusCodeSame(201);

        $this->invokeGenerateCommand($client->getKernel(), '2026-01-31');

        $client->request('GET', '/api/transactions', server: $this->authHeaders($token));
        self::assertCount(0, json_decode($client->getResponse()->getContent(), true));
    }
}
