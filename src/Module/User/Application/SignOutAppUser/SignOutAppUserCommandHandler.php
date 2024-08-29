<?php
declare(strict_types=1);

namespace App\Module\User\Application\SignOutAppUser;

use App\Module\Shared\Application\UserResponse;
use App\Module\Shared\Domain\Bus\Command\CommandHandler;
use App\Module\User\Domain\Contract\AuthUserServiceInterface;

class SignOutAppUserCommandHandler implements CommandHandler
{
    private AuthUserServiceInterface $service;

    public function __construct(AuthUserServiceInterface $service)
    {
        $this->service = $service;
    }

    public function __invoke(SignOutAppUserCommand $command): UserResponse
    {
        $user = $this->service->signOut($command->userId());

        $response = new UserResponse();

        $response->user = $user;

        return $response;
    }
}
