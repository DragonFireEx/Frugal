<?php

namespace App\Tests\Controller;

class TransactionControllerTest extends ApiTestCase
{
    public function testCrudWithMonthFiltering(): void
    {
        $client = static::createClient();
        $token = $this->registerAndLogin($client, 'transaction');

        $client->request('POST', '/api/categories', server: $this->jsonHeaders($token), content: json_encode([
            'name' => 'Salary',
            'type' => 'income',
        ]));
        self::assertResponseStatusCodeSame(201);
        $category = json_decode($client->getResponse()->getContent(), true);

        $client->request('POST', '/api/transactions', server: $this->jsonHeaders($token), content: json_encode([
            'categoryId' => $category['id'],
            'amount' => '100.00',
            'date' => '2026-01-15',
        ]));
        self::assertResponseStatusCodeSame(201);
        $januaryTransaction = json_decode($client->getResponse()->getContent(), true);

        $client->request('POST', '/api/transactions', server: $this->jsonHeaders($token), content: json_encode([
            'categoryId' => $category['id'],
            'amount' => '50.00',
            'date' => '2026-02-15',
        ]));
        self::assertResponseStatusCodeSame(201);

        $client->request('GET', '/api/transactions?month=2026-01', server: $this->authHeaders($token));
        self::assertResponseIsSuccessful();
        $januaryList = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(1, $januaryList);
        self::assertSame('100.00', $januaryList[0]['amount']);

        $client->request('GET', '/api/transactions', server: $this->authHeaders($token));
        self::assertResponseIsSuccessful();
        self::assertCount(2, json_decode($client->getResponse()->getContent(), true));

        $client->request('PUT', '/api/transactions/'.$januaryTransaction['id'], server: $this->jsonHeaders($token), content: json_encode([
            'categoryId' => $category['id'],
            'amount' => '150.00',
            'date' => '2026-01-15',
        ]));
        self::assertResponseIsSuccessful();
        $updated = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('150.00', $updated['amount']);

        $client->request('DELETE', '/api/transactions/'.$januaryTransaction['id'], server: $this->authHeaders($token));
        self::assertResponseStatusCodeSame(204);

        $client->request('GET', '/api/transactions?month=2026-01', server: $this->authHeaders($token));
        self::assertResponseIsSuccessful();
        self::assertCount(0, json_decode($client->getResponse()->getContent(), true));
    }
}
