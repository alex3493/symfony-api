<?php
declare(strict_types=1);

namespace App\Module\User\Application\Admin\AdminUpdateUser;

use App\Module\Shared\Application\UserResponse;
use App\Module\Shared\Domain\Bus\Command\CommandHandler;
use App\Module\User\Domain\Contract\UserCommandServiceInterface;

class AdminUpdateUserCommandHandler implements CommandHandler
{
    private UserCommandServiceInterface $service;

    public function __construct(UserCommandServiceInterface $service)
    {
        $this->service = $service;
    }

    public function __invoke(AdminUpdateUserCommand $command): UserResponse
    {
        $user = $this->service->adminUpdate($command->id(), $command->email(), $command->password(),
            $command->firstName(), $command->lastName(), $command->roles());

        $response = new UserResponse();
        $response->user = $user;

        return $response;
    }
}
