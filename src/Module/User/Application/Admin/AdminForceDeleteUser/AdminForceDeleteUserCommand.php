<?php
declare(strict_types=1);

namespace App\Module\User\Application\Admin\AdminForceDeleteUser;

use App\Module\Shared\Domain\Bus\Command\Command;

class AdminForceDeleteUserCommand implements Command
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
