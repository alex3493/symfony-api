<?php
declare(strict_types=1);

namespace App\Module\User\Application\Admin\AdminCreateUser;

use App\Module\Shared\Domain\Bus\Command\Command;
use App\Module\Shared\Domain\Bus\Command\ValidatedMessageInterface;
use App\Module\User\Domain\ValueObject\UserRole;

readonly class AdminCreateUserCommand implements Command, ValidatedMessageInterface
{
    /**
     * @param string $email
     * @param string|null $password
     * @param string|null $firstName
     * @param string|null $lastName
     * @param array<UserRole> $roles
     */
    public function __construct(
        private string $email, private ?string $password, private ?string $firstName, private ?string $lastName,
        private array $roles = [UserRole::ROLE_USER]
    ) {
    }

    public function email(): string
    {
        return $this->email;
    }

    public function password(): ?string
    {
        return $this->password;
    }

    public function firstName(): string
    {
        return $this->firstName;
    }

    public function lastName(): string
    {
        return $this->lastName;
    }

    public function roles(): array
    {
        return $this->roles;
    }

    public function validationContext(): string
    {
        return 'User';
    }
}
