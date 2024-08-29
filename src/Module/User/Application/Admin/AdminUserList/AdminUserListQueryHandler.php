<?php
declare(strict_types=1);

namespace App\Module\User\Application\Admin\AdminUserList;

use App\Module\Shared\Application\PaginatedListResponse;
use App\Module\Shared\Domain\Bus\Query\QueryHandler;
use App\Module\User\Domain\Contract\UserQueryServiceInterface;

class AdminUserListQueryHandler implements QueryHandler
{
    private UserQueryServiceInterface $service;

    public function __construct(UserQueryServiceInterface $service)
    {
        $this->service = $service;
    }

    public function __invoke(AdminUserListQuery $query): PaginatedListResponse
    {
        $list = $this->service->list($query->numPage(), $query->limit(), $query->orderBy(), $query->orderType(),
            $query->withDeleted());

        $response = new PaginatedListResponse();
        $response->items = $list['items'];
        $response->totalItems = $list['totalItems'];
        $response->totalPages = $list['totalPages'];

        return $response;
    }
}
