<?php
declare(strict_types=1);

namespace App\Module\User\Application\ChangePassword;

use App\Module\Shared\Domain\Bus\Command\Command;
use App\Module\Shared\Domain\Bus\Command\ValidatedMessageInterface;

class ChangePasswordCommand implements Command, ValidatedMessageInterface
{
    private string $id;

    private string $currentPassword;

    private string $password;

    private string $passwordConfirmation;

    /**
     * @param string $id
     * @param string $currentPassword
     * @param string $password
     * @param string $passwordConfirmation
     */
    public function __construct(
        string $id, string $currentPassword, string $password, string $passwordConfirmation
    ) {
        $this->id = $id;
        $this->currentPassword = $currentPassword;
        $this->password = $password;
        $this->passwordConfirmation = $passwordConfirmation;
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
