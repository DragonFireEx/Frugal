<?php

namespace App\Tests\Controller;

class RecurringTransactionControllerTest extends ApiTestCase
{
    public function testCrudHappyPath(): void
    {
        $client = static::createClient();
        $token = $this->registerAndLogin($client, 'recurring-crud');

        $client->request('POST', '/api/categories', server: $this->jsonHeaders($token), content: json_encode([
            'name' => 'Subskrypcje',
            'type' => 'expense',
        ]));
        self::assertResponseStatusCodeSame(201);
        $category = json_decode($client->getResponse()->getContent(), true);

        $client->request('POST', '/api/recurring-transactions', server: $this->jsonHeaders($token), content: json_encode([
            'categoryId' => $category['id'],
            'amount' => '9.99',
            'description' => 'Streaming',
            'frequency' => 'monthly',
            'nextRunDate' => '2026-10-01',
        ]));
        self::assertResponseStatusCodeSame(201);
        $recurring = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('9.99', $recurring['amount']);
        self::assertSame('monthly', $recurring['frequency']);
        self::assertTrue($recurring['active']);

        $client->request('GET', '/api/recurring-transactions', server: $this->authHeaders($token));
        self::assertResponseIsSuccessful();
        self::assertCount(1, json_decode($client->getResponse()->getContent(), true));

        $client->request('PUT', '/api/recurring-transactions/'.$recurring['id'], server: $this->jsonHeaders($token), content: json_encode([
            'categoryId' => $category['id'],
            'amount' => '12.99',
            'description' => 'Streaming (podwyżka)',
            'frequency' => 'monthly',
            'nextRunDate' => '2026-10-01',
            'active' => false,
        ]));
        self::assertResponseIsSuccessful();
        $updated = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('12.99', $updated['amount']);
        self::assertFalse($updated['active']);

        $client->request('DELETE', '/api/recurring-transactions/'.$recurring['id'], server: $this->authHeaders($token));
        self::assertResponseStatusCodeSame(204);

        $client->request('GET', '/api/recurring-transactions', server: $this->authHeaders($token));
        self::assertCount(0, json_decode($client->getResponse()->getContent(), true));
    }

    public function testCreateRejectsInvalidFrequency(): void
    {
        $client = static::createClient();
        $token = $this->registerAndLogin($client, 'recurring-invalid');

        $client->request('POST', '/api/categories', server: $this->jsonHeaders($token), content: json_encode([
            'name' => 'Subskrypcje',
            'type' => 'expense',
        ]));
        self::assertResponseStatusCodeSame(201);
        $category = json_decode($client->getResponse()->getContent(), true);

        $client->request('POST', '/api/recurring-transactions', server: $this->jsonHeaders($token), content: json_encode([
            'categoryId' => $category['id'],
            'amount' => '9.99',
            'frequency' => 'daily',
            'nextRunDate' => '2026-10-01',
        ]));
        self::assertResponseStatusCodeSame(400);
    }

    public function testCannotAccessAnotherUsersRecurringTransaction(): void
    {
        $client = static::createClient();
        $ownerToken = $this->registerAndLogin($client, 'recurring-owner');

        $client->request('POST', '/api/categories', server: $this->jsonHeaders($ownerToken), content: json_encode([
            'name' => 'Subskrypcje',
            'type' => 'expense',
        ]));
        self::assertResponseStatusCodeSame(201);
        $category = json_decode($client->getResponse()->getContent(), true);

        $client->request('POST', '/api/recurring-transactions', server: $this->jsonHeaders($ownerToken), content: json_encode([
            'categoryId' => $category['id'],
            'amount' => '9.99',
            'frequency' => 'monthly',
            'nextRunDate' => '2026-10-01',
        ]));
        self::assertResponseStatusCodeSame(201);
        $recurring = json_decode($client->getResponse()->getContent(), true);

        $intruderToken = $this->registerAndLogin($client, 'recurring-intruder');

        $client->request('DELETE', '/api/recurring-transactions/'.$recurring['id'], server: $this->authHeaders($intruderToken));
        self::assertResponseStatusCodeSame(403);
    }
}
