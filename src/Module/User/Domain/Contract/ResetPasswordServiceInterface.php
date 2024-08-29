<?php
declare(strict_types=1);

namespace App\Module\User\Domain\Contract;

interface ResetPasswordServiceInterface
{
    public function resetPassword(string $token, string $email, string $password);

    public function generateResetPasswordToken(string $email);
}
