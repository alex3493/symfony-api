<?php
declare(strict_types=1);

namespace App\Module\User\Domain;

readonly class ResetPasswordToken
{
    public function __construct(
        private string $email, private string $resetToken, private ?\DateTime $validUntil
    ) {
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getResetToken(): string
    {
        return $this->resetToken;
    }

    public function getValidUntil(): \DateTime
    {
        return $this->validUntil;
    }

    public function isValid(): bool
    {
        return is_null($this->validUntil) || $this->validUntil > new \DateTime();
    }
}
