<?php
declare(strict_types=1);

namespace App\Module\User\Application\LoginAppUser;

use App\Module\Shared\Domain\Bus\Command\Command;

class LoginAppUserCommand implements Command
{
    private string $email;

    private string $password;

    private ?string $deviceName;

    public function __construct(string $email, string $password, ?string $deviceName = null)
    {
        $this->email = $email;
        $this->password = $password;
        $this->deviceName = $deviceName;
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
