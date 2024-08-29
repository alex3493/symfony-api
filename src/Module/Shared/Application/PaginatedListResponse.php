<?php
declare(strict_types=1);

namespace App\Module\Shared\Application;

use App\Module\Shared\Domain\Bus\Contract\QueryResponse;
use Doctrine\ORM\Tools\Pagination\Paginator;

class PaginatedListResponse implements QueryResponse
{
    public array|Paginator $items;

    public int $totalPages;

    public int $totalItems;
}
