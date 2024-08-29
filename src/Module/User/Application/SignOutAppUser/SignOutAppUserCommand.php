<?php
declare(strict_types=1);

namespace App\Module\User\Application\SignOutAppUser;

use App\Module\Shared\Domain\Bus\Command\Command;

class SignOutAppUserCommand implements Command
{
    private string $userId;

    public function __construct(string $userId)
    {
        $this->userId = $userId;
    }

    public function userId(): string
    {
        return $this->userId;
    }
}
