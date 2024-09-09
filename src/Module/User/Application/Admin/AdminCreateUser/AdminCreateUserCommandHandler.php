<?php
declare(strict_types=1);

namespace App\Module\User\Application\Admin\AdminCreateUser;

use App\Module\Shared\Application\UserResponse;
use App\Module\Shared\Domain\Bus\Command\CommandHandler;
use App\Module\User\Domain\Contract\UserCommandServiceInterface;

readonly class AdminCreateUserCommandHandler implements CommandHandler
{
    /**
     * @param \App\Module\User\Domain\Contract\UserCommandServiceInterface $service
     */
    public function __construct(private UserCommandServiceInterface $service)
    {
    }

    public function __invoke(AdminCreateUserCommand $command): UserResponse
    {
        $user = $this->service->create($command->email(), $command->password(), $command->firstName(),
            $command->lastName(), $command->roles());

        return new UserResponse($user);
    }
}
