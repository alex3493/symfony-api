<?php
declare(strict_types=1);

namespace App\Module\User\Infrastructure\Persistence\Doctrine;

use App\Module\Shared\Infrastructure\Persistence\Trait\RelatedRepositoryTrait;
use App\Module\User\Domain\ResetPasswordToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ResetPasswordTokenRepository extends ServiceEntityRepository
{
    use RelatedRepositoryTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ResetPasswordToken::class);
    }

    public function save(ResetPasswordToken $resetToken): void
    {
        $this->getEntityManager()->persist($resetToken);
        $this->getEntityManager()->flush();
    }

    public function delete(ResetPasswordToken $resetToken): void
    {
        $this->getEntityManager()->remove($resetToken);
        $this->getEntityManager()->flush();
    }

    public function findByUser(string $email): ?ResetPasswordToken
    {
        return $this->findOneBy([
            'email' => $email,
        ]);
    }

    public function findByToken(string $token): ?ResetPasswordToken
    {
        return $this->findOneBy([
            'resetToken' => $token,
        ]);
    }
}
