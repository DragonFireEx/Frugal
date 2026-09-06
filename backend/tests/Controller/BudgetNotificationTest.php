<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;

class BudgetNotificationTest extends ApiTestCase
{
    use MailerAssertionsTrait;

    public function testEmailsOnceWhenCrossingBudgetAndNotAgainOnFurtherOverspend(): void
    {
        $client = static::createClient();
        $token = $this->registerAndLogin($client, 'budget-notify');

        $client->request('POST', '/api/categories', server: $this->jsonHeaders($token), content: json_encode([
            'name' => 'Groceries',
            'type' => 'expense',
        ]));
        self::assertResponseStatusCodeSame(201);
        $category = json_decode($client->getResponse()->getContent(), true);

        $client->request('POST', '/api/budgets', server: $this->jsonHeaders($token), content: json_encode([
            'categoryId' => $category['id'],
            'monthlyLimit' => '100.00',
        ]));
        self::assertResponseStatusCodeSame(201);

        // Under budget - no notification.
        $client->request('POST', '/api/transactions', server: $this->jsonHeaders($token), content: json_encode([
            'categoryId' => $category['id'],
            'amount' => '60.00',
            'date' => '2026-01-10',
        ]));
        self::assertResponseStatusCodeSame(201);
        self::assertEmailCount(0);

        // Crosses the limit (60 + 50 = 110 > 100) - exactly one notification.
        $client->request('POST', '/api/transactions', server: $this->jsonHeaders($token), content: json_encode([
            'categoryId' => $category['id'],
            'amount' => '50.00',
            'date' => '2026-01-15',
        ]));
        self::assertResponseStatusCodeSame(201);
        self::assertEmailCount(1);

        $email = self::getMailerMessage(0);
        self::assertNotNull($email);
        self::assertEmailSubjectContains($email, 'Groceries');

        // Already over budget - no further notification (mailer events are
        // collected per-request, so this checks zero *new* emails, not a
        // cumulative total).
        $client->request('POST', '/api/transactions', server: $this->jsonHeaders($token), content: json_encode([
            'categoryId' => $category['id'],
            'amount' => '10.00',
            'date' => '2026-01-20',
        ]));
        self::assertResponseStatusCodeSame(201);
        self::assertEmailCount(0);
    }
}
