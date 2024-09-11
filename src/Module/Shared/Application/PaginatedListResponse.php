<?php
declare(strict_types=1);

namespace App\Module\Shared\Application;

use App\Module\Shared\Domain\Bus\Contract\QueryResponse;
use Doctrine\ORM\Tools\Pagination\Paginator;

class PaginatedListResponse implements QueryResponse
{
    /**
     * @param array|\Doctrine\ORM\Tools\Pagination\Paginator $items
     * @param int $totalItems
     * @param int $totalPages
     */
    public function __construct(
        public array|Paginator $items, public int $totalItems, public int $totalPages
    ) {
    }
}
