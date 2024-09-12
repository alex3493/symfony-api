<?php

namespace App\Tests\Functional;

use App\Tests\DatabaseTestCase;

class NotificationTest extends DatabaseTestCase
{
    public function test_we_can_publish_mercure_update(): void
    {
        $user = static::$userSeeder->seedUser([], [
            ['name' => 'web', 'expiresAfter' => 24 * 60],
        ]);

        $client = $this->getAuthenticatedClient($user, false);

        $client->jsonRequest('POST', '/api/app/test-mercure', [
            'topic' => 'test-topic',
            'payload' => [
                'message' => 'Hi, there!',
                'status' => 'OK',
            ],
        ]);

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('OK', $response->message_dispatched);

        $this->assertResponseIsSuccessful();

        $this->transport('async')->queue()->assertNotEmpty();

        $this->transport('async')->process(1);

        // This assertion detects rejected message (e.g. exception occurred).
        $this->transport('async')->rejected()->assertEmpty();

        $this->transport('async')->queue()->assertEmpty();
    }

    public function test_authorize_mercure_subscription()
    {
        $user = static::$userSeeder->seedUser([], [], true);

        $client = $this->getAuthenticatedClient($user, true);

        $client->jsonRequest('GET', '/api/web/mercure-auth');

        $this->assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent());

        $this->assertNotEmpty($response->token);
    }
}
