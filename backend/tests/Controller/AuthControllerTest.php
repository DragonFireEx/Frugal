<?php

namespace App\Tests\Controller;

class AuthControllerTest extends ApiTestCase
{
    public function testRegisterLoginAndMe(): void
    {
        $client = static::createClient();

        $email = sprintf('auth-%s@example.com', bin2hex(random_bytes(4)));
        $password = 'Passw0rd!';

        $client->request('POST', '/api/register', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'email' => $email,
            'password' => $password,
            'name' => 'Auth Test',
        ]));
        self::assertResponseStatusCodeSame(201);

        $client->request('POST', '/api/login', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'email' => $email,
            'password' => $password,
        ]));
        self::assertResponseIsSuccessful();

        $login = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayHasKey('token', $login);

        $client->request('GET', '/api/me', server: $this->authHeaders($login['token']));
        self::assertResponseIsSuccessful();

        $me = json_decode($client->getResponse()->getContent(), true);
        self::assertSame($email, $me['email']);
        self::assertSame('Auth Test', $me['name']);
    }

    public function testMeWithoutTokenReturns401(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/me');

        self::assertResponseStatusCodeSame(401);
    }
}
