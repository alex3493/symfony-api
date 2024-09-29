<?php
declare(strict_types=1);

namespace App\Module\User\Domain\Contract;

use App\Module\User\Domain\User;
use Doctrine\Common\Collections\Order;

interface UserQueryServiceInterface
{
    public function findByEmail(string $email): ?User;

    public function findById(string $id): ?User;

    public function freshUserById(string $id): ?User;

    public function list(
        int $numPage, int $limit, string $orderBy, Order $orderType, string $search, bool $withDeleted
    ): array;
}
