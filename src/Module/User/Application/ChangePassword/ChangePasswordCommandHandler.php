<?php
declare(strict_types=1);

namespace App\Module\User\Application\ChangePassword;

use App\Module\Shared\Application\UserResponse;
use App\Module\Shared\Domain\Bus\Command\CommandHandler;
use App\Module\User\Domain\Contract\AuthUserServiceInterface;

class ChangePasswordCommandHandler implements CommandHandler
{
    private AuthUserServiceInterface $service;

    public function __construct(AuthUserServiceInterface $service)
    {
        $this->service = $service;
    }

    public function __invoke(ChangePasswordCommand $command): UserResponse
    {
        $user = $this->service->changePassword($command->id(), $command->currentPassword(), $command->password());

        $response = new UserResponse();

        $response->user = $user;

        return $response;
    }
}
