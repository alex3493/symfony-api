<?php
declare(strict_types=1);

namespace App\Module\User\Application\Admin\AdminForceDeleteUser;

use App\Module\Shared\Application\MessageResponse;
use App\Module\Shared\Domain\Bus\Command\CommandHandler;
use App\Module\User\Domain\Contract\UserCommandServiceInterface;

class AdminForceDeleteUserCommandHandler implements CommandHandler
{
    private UserCommandServiceInterface $service;

    public function __construct(UserCommandServiceInterface $service)
    {
        $this->service = $service;
    }

    public function __invoke(AdminForceDeleteUserCommand $command): MessageResponse
    {
        $this->service->forceDelete($command->userId());

        return new MessageResponse('User successfully deleted.');
    }
}
