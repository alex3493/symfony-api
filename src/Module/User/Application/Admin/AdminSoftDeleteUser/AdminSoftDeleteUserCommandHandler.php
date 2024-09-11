<?php
declare(strict_types=1);

namespace App\Module\User\Application\Admin\AdminSoftDeleteUser;

use App\Module\Shared\Application\UserResponse;
use App\Module\Shared\Domain\Bus\Command\CommandHandler;
use App\Module\User\Domain\Contract\UserCommandServiceInterface;

readonly class AdminSoftDeleteUserCommandHandler implements CommandHandler
{
    public function __construct(private UserCommandServiceInterface $service)
    {
    }

    public function __invoke(AdminSoftDeleteUserCommand $command): UserResponse
    {
        $user = $this->service->softDelete($command->userId());

        return new UserResponse($user);
    }
}
