<?php
declare(strict_types=1);

namespace App\Module\User\Application\LogoutAppUserDevice;

use App\Module\Shared\Application\UserResponse;
use App\Module\Shared\Domain\Bus\Command\CommandHandler;
use App\Module\User\Domain\Contract\AuthUserServiceInterface;

class LogoutAppUserDeviceCommandHandler implements CommandHandler
{
    private AuthUserServiceInterface $service;

    public function __construct(AuthUserServiceInterface $service)
    {
        $this->service = $service;
    }

    public function __invoke(LogoutAppUserDeviceCommand $command): UserResponse
    {
        $user = $this->service->logout($command->tokenId());

        $response = new UserResponse();

        $response->user = $user;

        return $response;
    }
}
