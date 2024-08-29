<?php
declare(strict_types=1);

namespace App\Module\User\Domain\Contract;

use App\Module\User\Domain\User;

interface UserCommandServiceInterface
{
    public function create(
        string $email, string $password, ?string $firstName, ?string $lastName, array $roles = []
    ): User;

    public function updateProfile(string $id, string $email, ?string $firstName, ?string $lastName): User;

    public function adminUpdate(
        string $id, string $email, string $password, ?string $firstName, ?string $lastName, array $roles
    ): User;

    public function logout(string $id): void;

    public function save(User $user): User;

    public function softDelete(string $id): User;

    public function restore(string $id): User;

    public function forceDelete(string $id): void;
}
