<?php
declare(strict_types=1);

namespace App\Module\User\Application\ChangePassword;

use App\Module\Shared\Application\UserResponse;
use App\Module\Shared\Domain\Bus\Command\CommandHandler;
use App\Module\User\Domain\Contract\AuthUserServiceInterface;

readonly class ChangePasswordCommandHandler implements CommandHandler
{
    /**
     * @param \App\Module\User\Domain\Contract\AuthUserServiceInterface $service
     */
    public function __construct(private AuthUserServiceInterface $service)
    {
    }

    public function __invoke(ChangePasswordCommand $command): UserResponse
    {
        $user = $this->service->changePassword($command->id(), $command->currentPassword(), $command->password());

        return new UserResponse($user);
    }
}
