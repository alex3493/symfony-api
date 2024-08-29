<?php
declare(strict_types=1);

namespace App\Module\User\Application\Admin\AdminUpdateUser;

use App\Module\Shared\Domain\Bus\Command\Command;
use App\Module\Shared\Domain\Bus\Command\ValidatedMessageInterface;
use App\Module\User\Domain\ValueObject\UserRole;

class AdminUpdateUserCommand implements Command, ValidatedMessageInterface
{
    private string $id;

    private string $email;

    private ?string $password;

    private ?string $firstName;

    private ?string $lastName;

    private array $roles;

    /**
     * @param string $id
     * @param string $email
     * @param string|null $password
     * @param string|null $firstName
     * @param string|null $lastName
     * @param array $roles
     */
    public function __construct(
        string $id, string $email, ?string $password, ?string $firstName, ?string $lastName,
        array $roles = [UserRole::ROLE_USER]
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->password = $password;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->roles = $roles;
    }

    public function id(): string
    {
        return $this->id;
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
