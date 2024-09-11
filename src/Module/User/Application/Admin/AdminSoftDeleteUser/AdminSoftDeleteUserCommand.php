<?php
declare(strict_types=1);

namespace App\Module\User\Application\Admin\AdminSoftDeleteUser;

use App\Module\Shared\Domain\Bus\Command\Command;

readonly class AdminSoftDeleteUserCommand implements Command
{
    /**
     * @param string $userId
     */
    public function __construct(private string $userId)
    {
    }

    public function userId(): string
    {
        return $this->userId;
    }
}
