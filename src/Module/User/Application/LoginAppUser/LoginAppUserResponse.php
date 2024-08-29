<?php
declare(strict_types=1);

namespace App\Module\User\Application\LoginAppUser;

use App\Module\Shared\Domain\Bus\Contract\CommandResponse;
use App\Module\User\Domain\User;

class LoginAppUserResponse implements CommandResponse
{
    public string $token;

    public User $user;
}
