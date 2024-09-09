<?php
declare(strict_types=1);

namespace App\Module\User\Application\RegisterWebUser;

use App\Module\Shared\Application\UserResponse;
use App\Module\Shared\Domain\Bus\Command\CommandHandler;
use App\Module\User\Domain\Contract\UserCommandServiceInterface;

readonly class RegisterWebUserCommandHandler implements CommandHandler
{
    /**
     * @param \App\Module\User\Domain\Contract\UserCommandServiceInterface $service
     */
    public function __construct(private UserCommandServiceInterface $service)
    {
    }

    /**
     * @param \App\Module\User\Application\RegisterWebUser\RegisterWebUserCommand $command
     * @return \App\Module\Shared\Application\UserResponse
     */
    public function __invoke(RegisterWebUserCommand $command): UserResponse
    {
        $user = $this->service->create($command->email(), $command->password(), $command->firstName(),
            $command->lastName());

        return new UserResponse($user);
    }
}
