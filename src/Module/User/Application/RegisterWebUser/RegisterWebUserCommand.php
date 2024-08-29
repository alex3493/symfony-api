<?php
declare(strict_types=1);

namespace App\Module\User\Application\RegisterWebUser;

use App\Module\Shared\Domain\Bus\Command\Command;
use App\Module\Shared\Domain\Bus\Command\ValidatedMessageInterface;

class RegisterWebUserCommand implements Command, ValidatedMessageInterface
{
    private string $email;

    private string $password;

    private string $passwordConfirmation;

    private ?string $firstName;

    private ?string $lastName;

    /**
     * @param string $email
     * @param string $password
     * @param string $passwordConfirmation
     * @param string|null $firstName
     * @param string|null $lastName
     */
    public function __construct(
        string $email, string $password, string $passwordConfirmation, ?string $firstName, ?string $lastName
    ) {
        $this->email = $email;
        $this->password = $password;
        $this->passwordConfirmation = $passwordConfirmation;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
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

    public function validationContext(): string
    {
        return 'User';
    }
}
