<?php
declare(strict_types=1);

namespace App\Module\Shared\Domain\Event;

use App\Module\Shared\Domain\ValueObject\EntityId;

abstract class DomainEvent
{
    private string $eventId;

    private \DateTime $occurredOn;

    public function __construct(?string $eventId = null, ?\DateTime $occurredOn = null)
    {
        $this->eventId = $eventId ?: EntityId::create()->getValue();
        $this->occurredOn = $occurredOn ?: new \DateTime();
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
