<?php
declare(strict_types=1);

namespace App\Module\User\Application\LoginAppUser;

use App\Module\Shared\Domain\Bus\Command\Command;

readonly class LoginAppUserCommand implements Command
{
    /**
     * @param string $email
     * @param string $password
     * @param string|null $deviceName
     */
    public function __construct(private string $email, private string $password, private ?string $deviceName = null)
    {
    }

    public function email(): string
    {
        return $this->email;
    }

    public function password(): string
    {
        return $this->password;
    }

    public function deviceName(): ?string
    {
        return $this->deviceName;
    }
}
