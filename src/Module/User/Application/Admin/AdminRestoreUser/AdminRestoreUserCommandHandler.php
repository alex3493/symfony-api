<?php
declare(strict_types=1);

namespace App\Module\User\Application\Admin\AdminRestoreUser;

use App\Module\Shared\Application\UserResponse;
use App\Module\Shared\Domain\Bus\Command\CommandHandler;
use App\Module\User\Domain\Contract\UserCommandServiceInterface;

class AdminRestoreUserCommandHandler implements CommandHandler
{
    public function __construct(private UserCommandServiceInterface $service)
    {
    }

    public function __invoke(AdminRestoreUserCommand $command): UserResponse
    {
        $user = $this->service->restore($command->userId());

        return new UserResponse($user);
    }
}
