<?php
declare(strict_types=1);

namespace App\Module\User\Domain\Contract;

interface AuthUserServiceInterface
{
    public function register(
        string $email, string $password, ?string $firstName, ?string $lastName, ?string $deviceName = null
    );

    public function login(string $email, string $password, ?string $deviceName = null);

    public function logout(string $tokenId);

    public function signOut(string $userId);

    public function changePassword(string $userId, string $currentPassword, string $password);

    public function deleteAccount(string $id, string $password);
}
