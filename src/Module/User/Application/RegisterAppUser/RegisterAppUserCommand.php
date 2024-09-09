<?php
declare(strict_types=1);

namespace App\Module\User\Application\RegisterAppUser;

use App\Module\Shared\Domain\Bus\Command\Command;
use App\Module\Shared\Domain\Bus\Command\ValidatedMessageInterface;

readonly class RegisterAppUserCommand implements Command, ValidatedMessageInterface
{
    /**
     * @param string $email
     * @param string $password
     * @param string $passwordConfirmation
     * @param string|null $firstName
     * @param string|null $lastName
     * @param string|null $deviceName
     */
    public function __construct(
        private string $email, private string $password, private string $passwordConfirmation, private ?string $firstName, private ?string $lastName,
        private ?string $deviceName = null
    ) {
    }

    public function email(): string
    {
        return $this->email;
    }

    public function password(): string
    {
        return $this->password;
    }

    public function passwordConfirmation(): string
    {
        return $this->passwordConfirmation;
    }

    public function firstName(): ?string
    {
        return $this->firstName;
    }

    public function lastName(): ?string
    {
        return $this->lastName;
    }

    public function deviceName(): ?string
    {
        return $this->deviceName;
    }

    public function validationContext(): string
    {
        return 'User';
    }
}
