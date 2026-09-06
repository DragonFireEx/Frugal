<?php

namespace App\Tests\Controller;

class TagControllerTest extends ApiTestCase
{
    public function testCreateListAndDelete(): void
    {
        $client = static::createClient();
        $token = $this->registerAndLogin($client, 'tag');

        $client->request('POST', '/api/tags', server: $this->jsonHeaders($token), content: json_encode([
            'name' => 'Wakacje',
        ]));
        self::assertResponseStatusCodeSame(201);
        $tag = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('Wakacje', $tag['name']);

        $client->request('GET', '/api/tags', server: $this->authHeaders($token));
        self::assertResponseIsSuccessful();
        self::assertCount(1, json_decode($client->getResponse()->getContent(), true));

        $client->request('DELETE', '/api/tags/'.$tag['id'], server: $this->authHeaders($token));
        self::assertResponseStatusCodeSame(204);

        $client->request('GET', '/api/tags', server: $this->authHeaders($token));
        self::assertResponseIsSuccessful();
        self::assertCount(0, json_decode($client->getResponse()->getContent(), true));
    }

    public function testCreateRejectsBlankName(): void
    {
        $client = static::createClient();
        $token = $this->registerAndLogin($client, 'tag');

        $client->request('POST', '/api/tags', server: $this->jsonHeaders($token), content: json_encode([
            'name' => '',
        ]));
        self::assertResponseStatusCodeSame(400);
    }

    public function testCannotDeleteAnotherUsersTag(): void
    {
        $client = static::createClient();
        $ownerToken = $this->registerAndLogin($client, 'tag-owner');

        $client->request('POST', '/api/tags', server: $this->jsonHeaders($ownerToken), content: json_encode([
            'name' => 'Prywatny tag',
        ]));
        self::assertResponseStatusCodeSame(201);
        $tag = json_decode($client->getResponse()->getContent(), true);

        $otherToken = $this->registerAndLogin($client, 'tag-other');
        $client->request('DELETE', '/api/tags/'.$tag['id'], server: $this->authHeaders($otherToken));
        self::assertResponseStatusCodeSame(403);
    }
}
