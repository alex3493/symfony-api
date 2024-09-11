<?php
declare(strict_types=1);

namespace App\Module\User\Application\Admin\AdminUserList;

use App\Module\Shared\Domain\Bus\Query\Query;
use Doctrine\Common\Collections\Order;

readonly class AdminUserListQuery implements Query
{
    private Order $orderType;

    /**
     * @param int $numPage
     * @param int $limit
     * @param string $orderBy
     * @param string $orderType
     * @param bool $withDeleted
     */
    public function __construct(
        private int $numPage = 1, private int $limit = 15, private string $orderBy = 'id', string $orderType = 'ASC',
        private bool $withDeleted = false
    ) {
        $this->orderType = strtoupper($orderType) == 'ASC' ? Order::Ascending : Order::Descending;
    }

    public function numPage(): int
    {
        return $this->numPage;
    }

    public function limit(): int
    {
        return $this->limit;
    }

    public function orderBy(): string
    {
        return $this->orderBy;
    }

    public function orderType(): Order
    {
        return $this->orderType;
    }

    public function withDeleted(): bool
    {
        return $this->withDeleted;
    }
}
