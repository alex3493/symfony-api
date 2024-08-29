<?php
declare(strict_types=1);

namespace App\Module\User\Application\Admin\AdminSoftDeleteUser;

use App\Module\Shared\Domain\Bus\Command\Command;

class AdminSoftDeleteUserCommand implements Command
{
    public string $userId;

    public function __construct(string $userId)
    {
        $this->userId = $userId;
    }

    public function userId(): string
    {
        return $this->userId;
    }
}
