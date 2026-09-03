<?php

namespace App\Tests\Controller;

class CategoryControllerTest extends ApiTestCase
{
    public function testCrudHappyPath(): void
    {
        $client = static::createClient();
        $token = $this->registerAndLogin($client, 'category');

        $client->request('POST', '/api/categories', server: $this->jsonHeaders($token), content: json_encode([
            'name' => 'Groceries',
            'type' => 'expense',
        ]));
        self::assertResponseStatusCodeSame(201);
        $category = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('Groceries', $category['name']);

        $client->request('GET', '/api/categories', server: $this->authHeaders($token));
        self::assertResponseIsSuccessful();
        $list = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(1, $list);

        $client->request('PUT', '/api/categories/'.$category['id'], server: $this->jsonHeaders($token), content: json_encode([
            'name' => 'Groceries & Food',
            'type' => 'expense',
        ]));
        self::assertResponseIsSuccessful();
        $updated = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('Groceries & Food', $updated['name']);

        $client->request('DELETE', '/api/categories/'.$category['id'], server: $this->authHeaders($token));
        self::assertResponseStatusCodeSame(204);

        $client->request('GET', '/api/categories', server: $this->authHeaders($token));
        self::assertResponseIsSuccessful();
        self::assertCount(0, json_decode($client->getResponse()->getContent(), true));
    }

    public function testCannotAccessAnotherUsersCategory(): void
    {
        $client = static::createClient();
        $ownerToken = $this->registerAndLogin($client, 'owner');

        $client->request('POST', '/api/categories', server: $this->jsonHeaders($ownerToken), content: json_encode([
            'name' => 'Rent',
            'type' => 'expense',
        ]));
        self::assertResponseStatusCodeSame(201);
        $category = json_decode($client->getResponse()->getContent(), true);

        $intruderToken = $this->registerAndLogin($client, 'intruder');

        $client->request('PUT', '/api/categories/'.$category['id'], server: $this->jsonHeaders($intruderToken), content: json_encode([
            'name' => 'Hacked',
            'type' => 'expense',
        ]));
        self::assertResponseStatusCodeSame(403);

        $client->request('DELETE', '/api/categories/'.$category['id'], server: $this->authHeaders($intruderToken));
        self::assertResponseStatusCodeSame(403);
    }
}
