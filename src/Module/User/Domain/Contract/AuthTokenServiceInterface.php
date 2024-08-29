<?php
declare(strict_types=1);

namespace App\Module\User\Domain\Contract;

use App\Module\User\Domain\AuthToken;
use App\Module\User\Domain\User;

interface AuthTokenServiceInterface
{
    public function generateAndSaveToken(User $user, string $deviceName): AuthToken;

    public function find(string $tokenId): ?AuthToken;

    public function delete(AuthToken $authToken): void;

    public function existing(User $user, string $deviceName): ?AuthToken;
}
