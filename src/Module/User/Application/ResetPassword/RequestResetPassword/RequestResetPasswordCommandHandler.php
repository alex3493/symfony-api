<?php
declare(strict_types=1);

namespace App\Module\User\Application\ResetPassword\RequestResetPassword;

use App\Module\Shared\Domain\Bus\Command\CommandHandler;
use App\Module\User\Domain\Contract\ResetPasswordServiceInterface;

readonly class RequestResetPasswordCommandHandler implements CommandHandler
{
    /**
     * @param \App\Module\User\Domain\Contract\ResetPasswordServiceInterface $service
     */
    public function __construct(private ResetPasswordServiceInterface $service)
    {
    }

    public function __invoke(RequestResetPasswordCommand $command): void
    {
        $this->service->generateResetPasswordToken($command->email());
    }
}
