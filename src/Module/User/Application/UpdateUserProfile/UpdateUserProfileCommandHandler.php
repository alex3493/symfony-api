<?php
declare(strict_types=1);

namespace App\Module\User\Application\UpdateUserProfile;

use App\Module\Shared\Application\UserResponse;
use App\Module\Shared\Domain\Bus\Command\CommandHandler;
use App\Module\User\Domain\Contract\UserCommandServiceInterface;

class UpdateUserProfileCommandHandler implements CommandHandler
{
    private UserCommandServiceInterface $service;

    public function __construct(UserCommandServiceInterface $service)
    {
        $this->service = $service;
    }

    public function __invoke(UpdateUserProfileCommand $command): UserResponse
    {
        $user = $this->service->updateProfile($command->id(), $command->email(), $command->firstName(),
            $command->lastName());

        $response = new UserResponse();
        $response->user = $user;

        return $response;
    }
}
