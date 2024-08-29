<?php
declare(strict_types=1);

namespace App\Module\User\Application\DeleteAppUser;

use App\Module\Shared\Application\MessageResponse;
use App\Module\Shared\Domain\Bus\Command\CommandHandler;
use App\Module\User\Domain\Contract\AuthUserServiceInterface;

class DeleteAppUserCommandHandler implements CommandHandler
{
    private AuthUserServiceInterface $service;

    public function __construct(AuthUserServiceInterface $service)
    {
        $this->service = $service;
    }

    public function __invoke(DeleteAppUserCommand $command): MessageResponse
    {
        $this->service->deleteAccount($command->id(), $command->password());

        return new MessageResponse('User account deleted successfully');
    }
}
