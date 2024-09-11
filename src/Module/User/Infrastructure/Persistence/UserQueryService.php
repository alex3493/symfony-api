<?php
declare(strict_types=1);

namespace App\Module\User\Infrastructure\Persistence;

use App\Module\User\Domain\Contract\UserQueryServiceInterface;
use App\Module\User\Domain\User;
use App\Module\User\Infrastructure\Persistence\Doctrine\UserRepository;
use Doctrine\Common\Collections\Order;

readonly class UserQueryService implements UserQueryServiceInterface
{
    /**
     * @param \App\Module\User\Infrastructure\Persistence\Doctrine\UserRepository $repository
     */
    public function __construct(private UserRepository $repository)
    {
    }

    public function findByEmail(string $email): ?User
    {
        return $this->repository->findOneBy(['email' => $email]);
    }

    public function findById(string $id): ?User
    {
        return $this->repository->find($id);
    }

    // TODO: check if we really need this method!
    public function freshUserById(string $id): ?User
    {
        $user = $this->repository->find($id);
        $this->repository->refresh($user);

        return $user;
    }

    /**
     * @param int $numPage
     * @param int $limit
     * @param string $orderBy
     * @param \Doctrine\Common\Collections\Order $orderType
     * @param bool $withDeleted
     * @return array
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function list(
        int $numPage, int $limit, string $orderBy, Order $orderType, bool $withDeleted
    ): array {
        return $this->repository->list($numPage, $limit, $orderBy, $orderType, $withDeleted);
    }
}
