<?php
declare(strict_types=1);

namespace App\Module\User\Application\DeleteAppUser;

use App\Module\Shared\Application\MessageResponse;
use App\Module\Shared\Domain\Bus\Command\CommandHandler;
use App\Module\User\Domain\Contract\AuthUserServiceInterface;

readonly class DeleteAppUserCommandHandler implements CommandHandler
{
    /**
     * @param \App\Module\User\Domain\Contract\AuthUserServiceInterface $service
     */
    public function __construct(private AuthUserServiceInterface $service)
    {
    }

    public function __invoke(DeleteAppUserCommand $command): MessageResponse
    {
        $this->service->deleteAccount($command->id(), $command->password());

        return new MessageResponse('User account deleted successfully');
    }
}
