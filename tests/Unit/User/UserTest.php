<?php

namespace App\Tests\Unit\User;

use App\Module\Shared\Domain\Exception\UnprocessableEntityDomainException;
use App\Module\Shared\Domain\ValueObject\Email;
use App\Module\Shared\Domain\ValueObject\EntityId;
use App\Module\User\Domain\User;
use DateTime;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function test_create_user(): void
    {
        $user = User::create('test@example.com', 'password', 'First', 'Last', ['ROLE_USER']);
        self::assertInstanceOf(User::class, $user);

        self::assertIsString($user->getId());
        self::assertEquals(36, strlen($user->getId()));
    }

    public function test_create_user_email_validation(): void
    {
        self::expectException(UnprocessableEntityDomainException::class);
        User::create('invalid', 'password', 'First', 'Last', []);
    }

    public function test_create_user_role_validation(): void
    {
        self::expectException(UnprocessableEntityDomainException::class);
        User::create('test@example.com', 'password', 'First', 'Last', ['INVALID_ROLE']);
    }

    public function test_init_existing_user(): void
    {
        $user = new User(EntityId::create(), new Email('test@example.com'), 'password', 'First', 'Last', [],
            new DateTime());
        self::assertInstanceOf(User::class, $user);

        self::assertIsString($user->getId());
        self::assertEquals(36, strlen($user->getId()));
    }
}
