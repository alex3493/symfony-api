<?php
declare(strict_types=1);

namespace App\Module\User\Application\RegisterAppUser;

use App\Module\Shared\Domain\Bus\Command\CommandHandler;
use App\Module\User\Domain\Contract\AuthUserServiceInterface;

readonly class RegisterAppUserCommandHandler implements CommandHandler
{
    public function __construct(private AuthUserServiceInterface $service)
    {
    }

    /**
     * @param \App\Module\User\Application\RegisterAppUser\RegisterAppUserCommand $command
     * @return \App\Module\User\Application\RegisterAppUser\RegisterAppUserResponse
     */
    public function __invoke(RegisterAppUserCommand $command): RegisterAppUserResponse
    {
        [$user, $token] = $this->service->register($command->email(), $command->password(), $command->firstName(),
            $command->lastName(), $command->deviceName());

        return new RegisterAppUserResponse($user, $token);
    }
}
