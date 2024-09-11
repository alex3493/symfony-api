<?php
declare(strict_types=1);

namespace App\Module\User\Application\Admin\AdminUpdateUser;

use App\Module\Shared\Application\UserResponse;
use App\Module\Shared\Domain\Bus\Command\CommandHandler;
use App\Module\User\Domain\Contract\UserCommandServiceInterface;

readonly class AdminUpdateUserCommandHandler implements CommandHandler
{
    /**
     * @param \App\Module\User\Domain\Contract\UserCommandServiceInterface $service
     */
    public function __construct(private UserCommandServiceInterface $service)
    {
    }

    public function __invoke(AdminUpdateUserCommand $command): UserResponse
    {
        $user = $this->service->adminUpdate($command->id(), $command->email(), $command->password(),
            $command->firstName(), $command->lastName(), $command->roles());

        return new UserResponse($user);
    }
}
