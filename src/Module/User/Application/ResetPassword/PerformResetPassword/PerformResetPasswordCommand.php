<?php
declare(strict_types=1);

namespace App\Module\User\Application\ResetPassword\PerformResetPassword;

use App\Module\Shared\Domain\Bus\Command\Command;
use App\Module\Shared\Domain\Bus\Command\ValidatedMessageInterface;

class PerformResetPasswordCommand implements Command, ValidatedMessageInterface
{
    private string $email;

    private string $resetToken;

    private string $password;

    private string $passwordConfirmation;

    /**
     * @param string $email
     * @param string $resetToken
     * @param string $password
     * @param string $passwordConfirmation
     */
    public function __construct(string $email, string $resetToken, string $password, string $passwordConfirmation)
    {
        $this->email = $email;
        $this->resetToken = $resetToken;
        $this->password = $password;
        $this->passwordConfirmation = $passwordConfirmation;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function resetToken(): string
    {
        return $this->resetToken;
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
