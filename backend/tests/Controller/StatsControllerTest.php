<?php

namespace App\Tests\Controller;

class StatsControllerTest extends ApiTestCase
{
    public function testYearlyAggregatesIncomeAndExpensePerMonth(): void
    {
        $client = static::createClient();
        $token = $this->registerAndLogin($client, 'stats-yearly');

        $client->request('POST', '/api/categories', server: $this->jsonHeaders($token), content: json_encode([
            'name' => 'Salary',
            'type' => 'income',
        ]));
        self::assertResponseStatusCodeSame(201);
        $income = json_decode($client->getResponse()->getContent(), true);

        $client->request('POST', '/api/categories', server: $this->jsonHeaders($token), content: json_encode([
            'name' => 'Groceries',
            'type' => 'expense',
        ]));
        self::assertResponseStatusCodeSame(201);
        $expense = json_decode($client->getResponse()->getContent(), true);

        $client->request('POST', '/api/transactions', server: $this->jsonHeaders($token), content: json_encode([
            'categoryId' => $income['id'],
            'amount' => '1000.00',
            'date' => '2026-01-15',
        ]));
        self::assertResponseStatusCodeSame(201);

        $client->request('POST', '/api/transactions', server: $this->jsonHeaders($token), content: json_encode([
            'categoryId' => $expense['id'],
            'amount' => '300.00',
            'date' => '2026-01-20',
        ]));
        self::assertResponseStatusCodeSame(201);

        $client->request('POST', '/api/transactions', server: $this->jsonHeaders($token), content: json_encode([
            'categoryId' => $expense['id'],
            'amount' => '50.00',
            'date' => '2026-03-05',
        ]));
        self::assertResponseStatusCodeSame(201);

        // Outside the requested year - must not leak into the aggregation.
        $client->request('POST', '/api/transactions', server: $this->jsonHeaders($token), content: json_encode([
            'categoryId' => $income['id'],
            'amount' => '9999.00',
            'date' => '2025-01-15',
        ]));
        self::assertResponseStatusCodeSame(201);

        $client->request('GET', '/api/stats/yearly?year=2026', server: $this->authHeaders($token));
        self::assertResponseIsSuccessful();
        $stats = json_decode($client->getResponse()->getContent(), true);

        self::assertSame('2026', $stats['year']);
        self::assertCount(12, $stats['months']);

        $january = $stats['months'][0];
        self::assertSame('2026-01', $january['month']);
        self::assertSame('1000.00', $january['income']);
        self::assertSame('300.00', $january['expense']);
        self::assertSame('700.00', $january['balance']);

        $march = $stats['months'][2];
        self::assertSame('2026-03', $march['month']);
        self::assertSame('0.00', $march['income']);
        self::assertSame('50.00', $march['expense']);
        self::assertSame('-50.00', $march['balance']);

        $february = $stats['months'][1];
        self::assertSame('0.00', $february['income']);
        self::assertSame('0.00', $february['expense']);
    }

    public function testYearlyRejectsInvalidYearFormat(): void
    {
        $client = static::createClient();
        $token = $this->registerAndLogin($client, 'stats-yearly-invalid');

        $client->request('GET', '/api/stats/yearly?year=26', server: $this->authHeaders($token));
        self::assertResponseStatusCodeSame(400);
    }
}
