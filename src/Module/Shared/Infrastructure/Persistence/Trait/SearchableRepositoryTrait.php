<?php

namespace App\Module\Shared\Infrastructure\Persistence\Trait;

use Doctrine\ORM\QueryBuilder;

trait SearchableRepositoryTrait
{
    protected function addSearchQuery(QueryBuilder $qb, string $query, array $coalesceFields): void
    {
        if (! trim($query)) {
            return;
        }
        $coalesce = '';
        foreach ($coalesceFields as $field) {
            $coalesce .= "COALESCE({$field}, ''), ' ',";
        }

        // Split multi-word search query.
        $terms = array_filter(explode(' ', $query));
        $dql = [];
        foreach ($terms as $i => $term) {
            $dql[] = "CONCAT({$coalesce} '') LIKE :search_term_{$i}";
            $qb->setParameter("search_term_{$i}", '%'.trim($term).'%');
        }

        // Add every search term as LIKE condition.
        $dql = implode(' AND ', $dql);
        $qb->andWhere($dql);
    }
}