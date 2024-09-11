<?php
declare(strict_types=1);

namespace App\Module\User\Application\LogoutAppUserDevice;

use App\Module\Shared\Domain\Bus\Command\Command;

readonly class LogoutAppUserDeviceCommand implements Command
{
    /**
     * @param string $tokenId
     */
    public function __construct(private string $tokenId)
    {
    }

    public function tokenId(): string
    {
        return $this->tokenId;
    }
}
