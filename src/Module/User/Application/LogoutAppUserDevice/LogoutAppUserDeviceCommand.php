<?php
declare(strict_types=1);

namespace App\Module\User\Application\LogoutAppUserDevice;

use App\Module\Shared\Domain\Bus\Command\Command;

class LogoutAppUserDeviceCommand implements Command
{
    private string $tokenId;

    public function __construct(string $tokenId)
    {
        $this->tokenId = $tokenId;
    }

    public function tokenId(): string
    {
        return $this->tokenId;
    }
}
