<?php
declare(strict_types=1);

namespace App\Module\Shared\Domain\Event;

abstract class DomainEventAwareEntity
{
    /**
     * @var array<DomainEvent>
     */
    private array $domainEvents = [];

    /**
     * @param DomainEvent $domainEvent
     * @return void
     */
    public function record(DomainEvent $domainEvent): void
    {
        $this->domainEvents[] = $domainEvent;
    }

    /**
     * @return bool
     */
    public function hasDomainEvents(): bool
    {
        return count($this->domainEvents) > 0;
    }

    /**
     * @return DomainEvent[]
     */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
