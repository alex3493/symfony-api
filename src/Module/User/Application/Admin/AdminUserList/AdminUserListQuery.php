<?php
declare(strict_types=1);

namespace App\Module\User\Application\Admin\AdminUserList;

use App\Module\Shared\Domain\Bus\Query\Query;
use Doctrine\Common\Collections\Order;

class AdminUserListQuery implements Query
{
    private int $numPage;

    private int $limit;

    private string $orderBy;

    private Order $orderType;

    private bool $withDeleted;

    /**
     * @param int $numPage
     * @param int $limit
     * @param string $orderBy
     * @param string $orderType
     * @param bool $withDeleted
     */
    public function __construct(
        int $numPage = 1, int $limit = 15, string $orderBy = 'id', string $orderType = 'ASC', bool $withDeleted = false
    ) {
        $this->numPage = $numPage;
        $this->limit = $limit;
        $this->orderBy = $orderBy;
        $this->orderType = strtoupper($orderType) == 'ASC' ? Order::Ascending : Order::Descending;
        $this->withDeleted = $withDeleted;
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
