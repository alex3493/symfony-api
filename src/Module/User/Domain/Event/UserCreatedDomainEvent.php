<?php

namespace App\Module\User\Domain\Event;

use App\Module\Shared\Domain\Event\AsyncDomainEventInterface;
use App\Module\Shared\Domain\Event\DomainEvent;
use App\Module\User\Domain\User;

class UserCreatedDomainEvent extends DomainEvent implements AsyncDomainEventInterface
{
    public function __construct(private readonly User $user, string $eventId = null, \DateTime $occurredOn = null)
    {
        parent::__construct($eventId, $occurredOn);
    }

    public static function eventName(): string
    {
        return 'user.created';
    }

    public function toPrimitives(): array
    {
        return [
            'user' => $this->user,
        ];
    }

    public static function fromPrimitives(array $body, string $eventId, \DateTime $occurredOn): DomainEvent
    {
        return new self($body['user'], $eventId, $occurredOn);
    }
}
