<?php
declare(strict_types=1);

namespace App\Module\User\Application\Admin\AdminRestoreUser;

use App\Module\Shared\Domain\Bus\Command\Command;

readonly class AdminRestoreUserCommand implements Command
{
    public function __construct(private string $userId)
    {
    }

    public function userId(): string
    {
        return $this->userId;
    }
}
