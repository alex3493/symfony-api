<?php

namespace App\Tests\Unit\User;

use App\Module\User\Domain\AuthToken;
use App\Module\User\Domain\User;
use PHPUnit\Framework\TestCase;

class AuthTokenTest extends TestCase
{
    public function test_create_auth_token(): void
    {
        $user = User::create('test@example.com', 'password', 'First', 'Last', ['ROLE_USER']);
        $authToken = AuthToken::create($user, 'secret_token', 'device_name');
        self::assertInstanceOf(AuthToken::class, $authToken);

        self::assertIsString($authToken->getId());
        self::assertEquals(36, strlen($authToken->getId()));
    }
    // TODO: add test for token expiration - explore clock mocking for tests.
}
