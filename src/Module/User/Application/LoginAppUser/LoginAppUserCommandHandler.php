<?php
declare(strict_types=1);

namespace App\Module\User\Application\LoginAppUser;

use App\Module\Shared\Domain\Bus\Command\CommandHandler;
use App\Module\User\Domain\Contract\AuthUserServiceInterface;

class LoginAppUserCommandHandler implements CommandHandler
{
    private AuthUserServiceInterface $service;

    public function __construct(AuthUserServiceInterface $service)
    {
        $this->service = $service;
    }

    public function __invoke(LoginAppUserCommand $command): LoginAppUserResponse
    {
        [$user, $token] = $this->service->login($command->email(), $command->password(), $command->deviceName());

        $response = new LoginAppUserResponse();

        $response->token = $token;
        $response->user = $user;

        return $response;
    }
}
