<?php
declare(strict_types=1);

namespace App\Module\Shared\Infrastructure\Persistence\Trait;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;

trait RelatedRepositoryTrait
{
    /**
     * @param $className
     * @return EntityRepository
     * @throws NotSupported
     */
    public function getRelatedRepository($className): EntityRepository
    {
        if (! isset($this->_em)) {
            throw new NotSupported('RelatedRepositoryTrait can only be used with EntityRepository class or its descendants');
        }

        return $this->_em->getRepository($className);
    }
}
