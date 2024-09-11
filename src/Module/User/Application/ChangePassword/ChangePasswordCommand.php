<?php
declare(strict_types=1);

namespace App\Module\User\Application\ChangePassword;

use App\Module\Shared\Domain\Bus\Command\Command;
use App\Module\Shared\Domain\Bus\Command\ValidatedMessageInterface;

readonly class ChangePasswordCommand implements Command, ValidatedMessageInterface
{
    /**
     * @param string $id
     * @param string $currentPassword
     * @param string $password
     * @param string $passwordConfirmation
     */
    public function __construct(
        private string $id, private string $currentPassword, private string $password,
        private string $passwordConfirmation
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function currentPassword(): string
    {
        return $this->currentPassword;
    }

    public function password(): string
    {
        return $this->password;
    }

    public function passwordConfirmation(): string
    {
        return $this->passwordConfirmation;
    }

    public function validationContext(): string
    {
        return 'User';
    }
}
