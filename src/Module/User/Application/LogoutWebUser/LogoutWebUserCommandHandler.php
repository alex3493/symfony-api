<?php
declare(strict_types=1);

namespace App\Module\User\Application\LogoutWebUser;

use App\Module\Shared\Application\MessageResponse;
use App\Module\Shared\Domain\Bus\Command\CommandHandler;
use App\Module\User\Domain\Contract\UserCommandServiceInterface;

class LogoutWebUserCommandHandler implements CommandHandler
{
    private UserCommandServiceInterface $service;

    public function __construct(UserCommandServiceInterface $service)
    {
        $this->service = $service;
    }

    public function __invoke(LogoutWebUserCommand $command): MessageResponse
    {
        $this->service->logout($command->userId());

        return new MessageResponse('You have successfully logged out');
    }
}
