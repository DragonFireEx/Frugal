<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class ApiTestCase extends WebTestCase
{
    protected function registerAndLogin(KernelBrowser $client, string $emailPrefix): string
    {
        $email = sprintf('%s-%s@example.com', $emailPrefix, bin2hex(random_bytes(4)));
        $password = 'Passw0rd!';

        $client->request('POST', '/api/register', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'email' => $email,
            'password' => $password,
            'name' => 'Test User',
        ]));
        self::assertResponseStatusCodeSame(201);

        $client->request('POST', '/api/login', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'email' => $email,
            'password' => $password,
        ]));
        self::assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);

        return $data['token'];
    }

    /**
     * @return array<string, string>
     */
    protected function authHeaders(string $token): array
    {
        return ['HTTP_AUTHORIZATION' => 'Bearer '.$token];
    }

    /**
     * @return array<string, string>
     */
    protected function jsonHeaders(string $token): array
    {
        return $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'];
    }
}
