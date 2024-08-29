<?php
declare(strict_types=1);

namespace App\Module\User\Infrastructure\Persistence\Doctrine;

use App\Module\Shared\Infrastructure\Persistence\Trait\PaginatedRepositoryTrait;
use App\Module\Shared\Infrastructure\Persistence\Trait\RelatedRepositoryTrait;
use App\Module\User\Domain\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    use RelatedRepositoryTrait;
    use PaginatedRepositoryTrait;

    private array $orderByAccepted = [
        'id' => ['u.id'],
        'name' => ['u.lastName', 'u.firstName', 'u.email'],
        'email' => ['u.email'],
        'createdAt' => ['u.createdAt'],
    ];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $user): void
    {
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function delete(User $user): void
    {
        $this->getEntityManager()->remove($user);
        $this->getEntityManager()->flush();
    }

    public function refresh(User $user): void
    {
        $this->getEntityManager()->refresh($user);
    }

    public function findByIdWithoutFilter(string $id): ?User
    {
        if ($this->_em->getFilters()->isEnabled('softDeleted')) {
            $this->_em->getFilters()->disable('softDeleted');
        }
        $user = $this->find($id);

        // We are dealing with a global filter, so we have to clean up to avoid side effects.
        $this->_em->getFilters()->enable('softDeleted');

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
        int $numPage, int $limit, string $orderBy = 'name', Order $orderType = Order::Ascending,
        bool $withDeleted = false
    ): array {
        $qb = $this->createQueryBuilder('u');

        $criteria = Criteria::create();

        $this->addSelectedOrder($criteria, $orderBy, $orderType, $this->orderByAccepted);

        if ($withDeleted) {
            $this->_em->getFilters()->disable('softDeleted');
        }

        $qb->addCriteria($criteria);

        return $this->getItemsAndTotalsFromQueryBuilder($qb, $numPage, $limit);
    }
}
