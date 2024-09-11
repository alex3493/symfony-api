<?php
declare(strict_types=1);

namespace App\Module\User\Application\Admin\AdminForceDeleteUser;

use App\Module\Shared\Application\MessageResponse;
use App\Module\Shared\Domain\Bus\Command\CommandHandler;
use App\Module\User\Domain\Contract\UserCommandServiceInterface;

readonly class AdminForceDeleteUserCommandHandler implements CommandHandler
{
    /**
     * @param \App\Module\User\Domain\Contract\UserCommandServiceInterface $service
     */
    public function __construct(private UserCommandServiceInterface $service)
    {
    }

    public function __invoke(AdminForceDeleteUserCommand $command): MessageResponse
    {
        $this->service->forceDelete($command->userId());

        return new MessageResponse('User successfully deleted.');
    }
}
