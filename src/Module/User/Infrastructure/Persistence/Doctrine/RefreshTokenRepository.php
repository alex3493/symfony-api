<?php
declare(strict_types=1);

namespace App\Module\User\Infrastructure\Persistence\Doctrine;

use App\Module\User\Domain\Contract\RefreshTokenRepositoryInterface;
use App\Module\User\Domain\RefreshToken;

class RefreshTokenRepository extends \Gesdinet\JWTRefreshTokenBundle\Entity\RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    public function save(RefreshToken $refreshToken): void
    {
        $this->getEntityManager()->persist($refreshToken);
        $this->getEntityManager()->flush();
    }

    public function delete(RefreshToken $refreshToken): void
    {
        $this->getEntityManager()->remove($refreshToken);
        $this->getEntityManager()->flush();
    }

    public function findByUser(string $username): array
    {
        return $this->findBy([
            'username' => $username,
        ], ['valid' => 'DESC']);
    }
}
