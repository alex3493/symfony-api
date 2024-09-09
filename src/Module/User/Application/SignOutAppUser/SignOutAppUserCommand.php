<?php
declare(strict_types=1);

namespace App\Module\User\Application\SignOutAppUser;

use App\Module\Shared\Domain\Bus\Command\Command;

readonly class SignOutAppUserCommand implements Command
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
