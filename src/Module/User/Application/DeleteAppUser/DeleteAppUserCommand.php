<?php
declare(strict_types=1);

namespace App\Module\User\Application\DeleteAppUser;

use App\Module\Shared\Domain\Bus\Command\Command;

class DeleteAppUserCommand implements Command
{
    private string $id;

    private string $password;

    public function __construct(string $id, string $password)
    {
        $this->id = $id;
        $this->password = $password;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function password(): string
    {
        return $this->password;
    }
}
