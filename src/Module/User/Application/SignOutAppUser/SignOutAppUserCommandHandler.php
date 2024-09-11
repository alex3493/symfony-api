<?php
declare(strict_types=1);

namespace App\Module\User\Application\SignOutAppUser;

use App\Module\Shared\Application\UserResponse;
use App\Module\Shared\Domain\Bus\Command\CommandHandler;
use App\Module\User\Domain\Contract\AuthUserServiceInterface;

readonly class SignOutAppUserCommandHandler implements CommandHandler
{
    /**
     * @param \App\Module\User\Domain\Contract\AuthUserServiceInterface $service
     */
    public function __construct(private AuthUserServiceInterface $service)
    {
    }

    public function __invoke(SignOutAppUserCommand $command): UserResponse
    {
        $user = $this->service->signOut($command->userId());

        return new UserResponse($user);
    }
}
