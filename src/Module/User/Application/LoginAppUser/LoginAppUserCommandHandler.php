<?php
declare(strict_types=1);

namespace App\Module\User\Application\LoginAppUser;

use App\Module\Shared\Domain\Bus\Command\CommandHandler;
use App\Module\User\Domain\Contract\AuthUserServiceInterface;

class LoginAppUserCommandHandler implements CommandHandler
{
    /**
     * @param \App\Module\User\Domain\Contract\AuthUserServiceInterface $service
     */
    public function __construct(private AuthUserServiceInterface $service)
    {
    }

    public function __invoke(LoginAppUserCommand $command): LoginAppUserResponse
    {
        [$user, $token] = $this->service->login($command->email(), $command->password(), $command->deviceName());

        return new LoginAppUserResponse($token, $user);
    }
}
