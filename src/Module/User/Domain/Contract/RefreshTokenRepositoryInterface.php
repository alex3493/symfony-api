<?php
declare(strict_types=1);

namespace App\Module\User\Domain\Contract;

use App\Module\User\Domain\RefreshToken;

interface RefreshTokenRepositoryInterface
{
    public function save(RefreshToken $refreshToken): void;

    public function delete(RefreshToken $refreshToken): void;
}
