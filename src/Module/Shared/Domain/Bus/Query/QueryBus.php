<?php
declare(strict_types=1);

namespace App\Module\Shared\Domain\Bus\Query;

use App\Module\Shared\Domain\Bus\Contract\QueryResponse;

interface QueryBus
{
    /**
     * @param Query $query
     * @return QueryResponse
     */
    public function ask(Query $query): QueryResponse;
}
