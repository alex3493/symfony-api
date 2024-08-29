<?php
declare(strict_types=1);

namespace App\Module\User\Application\Admin\AdminRestoreUser;

use App\Module\Shared\Application\UserResponse;
use App\Module\Shared\Domain\Bus\Command\CommandHandler;
use App\Module\User\Domain\Contract\UserCommandServiceInterface;

class AdminRestoreUserCommandHandler implements CommandHandler
{
    private UserCommandServiceInterface $service;

    public function __construct(UserCommandServiceInterface $service)
    {
        $this->service = $service;
    }

    public function __invoke(AdminRestoreUserCommand $command): UserResponse
    {
        $user = $this->service->restore($command->userId());

        // TODO: Document it - we have doctrine listener that published entity domain events automatically on flush.

        $response = new UserResponse();
        $response->user = $user;

        return $response;
    }
}
