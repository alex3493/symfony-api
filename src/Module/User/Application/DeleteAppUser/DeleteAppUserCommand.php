<?php
declare(strict_types=1);

namespace App\Module\User\Application\DeleteAppUser;

use App\Module\Shared\Domain\Bus\Command\Command;

readonly class DeleteAppUserCommand implements Command
{
    /**
     * @param string $id
     * @param string $password
     */
    public function __construct(private string $id, private string $password)
    {
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
