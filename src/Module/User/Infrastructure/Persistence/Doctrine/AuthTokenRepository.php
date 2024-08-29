<?php
declare(strict_types=1);

namespace App\Module\User\Infrastructure\Persistence\Doctrine;

use App\Module\User\Domain\AuthToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AuthTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuthToken::class);
    }

    public function save(AuthToken $authToken): void
    {
        $this->getEntityManager()->persist($authToken);
        $this->getEntityManager()->flush();
    }

    public function delete(AuthToken $authToken): void
    {
        $this->getEntityManager()->remove($authToken);
        $this->getEntityManager()->flush();
    }
}
