<?php
declare(strict_types=1);

namespace App\Module\User\Application\LogoutWebUser;

use App\Module\Shared\Application\MessageResponse;
use App\Module\Shared\Domain\Bus\Command\CommandHandler;
use App\Module\User\Domain\Contract\UserCommandServiceInterface;

readonly class LogoutWebUserCommandHandler implements CommandHandler
{
    /**
     * @param \App\Module\User\Domain\Contract\UserCommandServiceInterface $service
     */
    public function __construct(private UserCommandServiceInterface $service)
    {
    }

    public function __invoke(LogoutWebUserCommand $command): MessageResponse
    {
        $this->service->logout($command->userId());

        return new MessageResponse('You have successfully logged out');
    }
}
