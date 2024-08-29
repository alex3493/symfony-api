<?php
declare(strict_types=1);

namespace App\Module\Shared\Infrastructure\Persistence\Trait;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Order;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

trait PaginatedRepositoryTrait
{
    public function paginate($query, $page, $limit): Paginator
    {
        $paginator = new Paginator($query);

        $paginator->getQuery()->setFirstResult($limit * ($page - 1))->setMaxResults($limit);

        return $paginator;
    }

    public function getItemsAndTotalsFromQueryBuilder(QueryBuilder $qb, int $page, int $limit): array
    {
        $query = $qb->getQuery();
        if ($limit) {
            $items = $this->paginate($query, $page, $limit);
        } else {
            $items = $query->getResult();
        }

        return [
            'items' => $items instanceof Paginator ? $items->getQuery()->getResult() : $items,
            'totalPages' => $this->getTotalPagesFromResult($items, $page, $limit),
            'totalItems' => $this->getTotalItemsFromResult($items),
        ];
    }

    public function getTotalPagesFromResult($result, $page = 0, $limit = 0): int
    {
        if (! $page || ! $limit) {
            return 1;
        }
        $total = $this->getTotalItemsFromResult($result);

        return intval(ceil($total / $limit));
    }

    /**
     * @param Paginator|array $result
     * @return int
     */
    public function getTotalItemsFromResult(Paginator|array $result): int
    {
        return count($result);
    }

    /**
     * @param Criteria $criteria
     * @param string $orderBy
     * @param Order $orderType
     * @param array $orderByAccepted
     * @return void
     */
    protected function addSelectedOrder(
        Criteria $criteria, string $orderBy, Order $orderType, array $orderByAccepted
    ): void {
        if (! array_key_exists($orderBy, $orderByAccepted)) {
            return;
        }

        $orders = [];
        foreach ($orderByAccepted[$orderBy] as $value) {
            $orders[$value] = $orderType;
        }

        $criteria->orderBy($orders);
    }
}
