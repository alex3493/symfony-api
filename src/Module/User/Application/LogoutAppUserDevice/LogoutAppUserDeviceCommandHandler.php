<?php
declare(strict_types=1);

namespace App\Module\User\Application\LogoutAppUserDevice;

use App\Module\Shared\Application\UserResponse;
use App\Module\Shared\Domain\Bus\Command\CommandHandler;
use App\Module\User\Domain\Contract\AuthUserServiceInterface;

readonly class LogoutAppUserDeviceCommandHandler implements CommandHandler
{
    /**
     * @param \App\Module\User\Domain\Contract\AuthUserServiceInterface $service
     */
    public function __construct(private AuthUserServiceInterface $service)
    {
    }

    public function __invoke(LogoutAppUserDeviceCommand $command): UserResponse
    {
        $user = $this->service->logout($command->tokenId());

        return new UserResponse($user);
    }
}
