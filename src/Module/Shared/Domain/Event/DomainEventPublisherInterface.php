<?php
declare(strict_types=1);

namespace App\Module\Shared\Domain\Event;

interface DomainEventPublisherInterface
{
    /**
     * @param array<DomainEvent> $domainEvents
     * @return void
     */
    public function publish(DomainEvent ...$domainEvents): void;
}
