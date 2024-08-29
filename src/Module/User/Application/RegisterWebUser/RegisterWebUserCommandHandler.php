<?php
declare(strict_types=1);

namespace App\Module\User\Application\RegisterWebUser;

use App\Module\Shared\Application\UserResponse;
use App\Module\Shared\Domain\Bus\Command\CommandHandler;
use App\Module\User\Domain\Contract\UserCommandServiceInterface;

class RegisterWebUserCommandHandler implements CommandHandler
{
    private UserCommandServiceInterface $service;

    public function __construct(UserCommandServiceInterface $service)
    {
        $this->service = $service;
    }

    /**
     * @param \App\Module\User\Application\RegisterWebUser\RegisterWebUserCommand $command
     * @return \App\Module\Shared\Application\UserResponse
     */
    public function __invoke(RegisterWebUserCommand $command): UserResponse
    {
        $user = $this->service->create($command->email(), $command->password(), $command->firstName(),
            $command->lastName());

        $response = new UserResponse();
        $response->user = $user;

        return $response;
    }
}
