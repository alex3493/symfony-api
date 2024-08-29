<?php
declare(strict_types=1);

namespace App\Module\User\Application\ResetPassword\PerformResetPassword;

use App\Module\Shared\Application\UserResponse;
use App\Module\Shared\Domain\Bus\Command\CommandHandler;
use App\Module\User\Domain\Contract\ResetPasswordServiceInterface;

class PerformResetPasswordCommandHandler implements CommandHandler
{
    private ResetPasswordServiceInterface $service;

    public function __construct(ResetPasswordServiceInterface $service)
    {
        $this->service = $service;
    }

    public function __invoke(PerformResetPasswordCommand $command): UserResponse
    {
        $user = $this->service->resetPassword($command->resetToken(), $command->email(), $command->password());

        $response = new UserResponse();
        $response->user = $user;

        return $response;
    }
}
