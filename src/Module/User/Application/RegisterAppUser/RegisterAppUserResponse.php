<?php
declare(strict_types=1);

namespace App\Module\User\Application\RegisterAppUser;

use App\Module\Shared\Domain\Bus\Contract\CommandResponse;
use App\Module\User\Domain\User;

class RegisterAppUserResponse implements CommandResponse
{
    public function __construct(public User $user, public string $token)
    {
    }
}
