<?php
declare(strict_types=1);

namespace App\Module\Shared\Infrastructure\Persistence\Filter;

use App\Module\Shared\Domain\Contract\SoftDeleteAwareEntityInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

class SoftDeleteFilter extends SQLFilter
{
    /**
     * @inheritDoc
     */
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        if (! in_array(SoftDeleteAwareEntityInterface::class,
            $targetEntity->getReflectionClass()->getInterfaceNames())) {
            return '';
        }

        return sprintf('%s.deleted IS NULL', $targetTableAlias);
    }
}
