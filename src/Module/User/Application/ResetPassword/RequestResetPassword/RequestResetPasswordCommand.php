<?php
declare(strict_types=1);

namespace App\Module\User\Application\ResetPassword\RequestResetPassword;

use App\Module\Shared\Domain\Bus\Command\AsyncCommand;
use App\Module\Shared\Domain\Bus\Command\ValidatedMessageInterface;

readonly class RequestResetPasswordCommand implements AsyncCommand, ValidatedMessageInterface
{
    /**
     * @param string $email
     */
    public function __construct(private string $email)
    {
    }

    public function issuedAt(): \DateTime
    {
        return new \DateTime();
    }

    public function email(): string
    {
        return $this->email;
    }

    public function validationContext(): string
    {
        return 'User';
    }
}
