<?php
declare(strict_types=1);

namespace App\Module\User\Application\ResetPassword\PerformResetPassword;

use App\Module\Shared\Application\UserResponse;
use App\Module\Shared\Domain\Bus\Command\CommandHandler;
use App\Module\User\Domain\Contract\ResetPasswordServiceInterface;

readonly class PerformResetPasswordCommandHandler implements CommandHandler
{
    /**
     * @param \App\Module\User\Domain\Contract\ResetPasswordServiceInterface $service
     */
    public function __construct(private ResetPasswordServiceInterface $service)
    {
    }

    public function __invoke(PerformResetPasswordCommand $command): UserResponse
    {
        $user = $this->service->resetPassword($command->resetToken(), $command->email(), $command->password());

        return new UserResponse($user);
    }
}
