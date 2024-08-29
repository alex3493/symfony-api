<?php
declare(strict_types=1);

namespace App\Module\User\Application\RegisterAppUser;

use App\Module\Shared\Domain\Bus\Command\CommandHandler;
use App\Module\User\Domain\Contract\AuthUserServiceInterface;

class RegisterAppUserCommandHandler implements CommandHandler
{
    private AuthUserServiceInterface $service;

    public function __construct(AuthUserServiceInterface $service)
    {
        $this->service = $service;
    }

    /**
     * @param \App\Module\User\Application\RegisterAppUser\RegisterAppUserCommand $command
     * @return \App\Module\User\Application\RegisterAppUser\RegisterAppUserResponse
     */
    public function __invoke(RegisterAppUserCommand $command): RegisterAppUserResponse
    {
        [$user, $token] = $this->service->register($command->email(), $command->password(), $command->firstName(),
            $command->lastName(), $command->deviceName());

        $response = new RegisterAppUserResponse();
        $response->user = $user;
        $response->token = $token;

        return $response;
    }
}
