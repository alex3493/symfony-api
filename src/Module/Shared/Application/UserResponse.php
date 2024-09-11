<?php
declare(strict_types=1);

namespace App\Module\Shared\Application;

use App\Module\Shared\Domain\Bus\Contract\CommandResponse;
use App\Module\User\Domain\User;

class UserResponse implements CommandResponse
{
    /**
     * @param \App\Module\User\Domain\User $user
     */
    public function __construct(public User $user)
    {
    }
}
