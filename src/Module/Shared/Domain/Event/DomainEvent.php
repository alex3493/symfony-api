<?php
declare(strict_types=1);

namespace App\Module\Shared\Domain\Event;

abstract class DomainEvent
{
    /**
     * @param string|null $eventId
     * @param \DateTime|null $occurredOn
     */
    public function __construct(
        private readonly ?string $eventId = null, private readonly ?\DateTime $occurredOn = null
    ) {
    }

    abstract public static function eventName(): string;

    abstract public function toPrimitives(): array;

    abstract public static function fromPrimitives(array $body, string $eventId, \DateTime $occurredOn): self;

    public function eventId(): string
    {
        return $this->eventId;
    }

    public function occurredOn(): \DateTime
    {
        return $this->occurredOn;
    }
}
