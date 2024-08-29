<?php
declare(strict_types=1);

namespace App\Module\User\Domain\Event;

use App\Module\Shared\Domain\Event\AsyncDomainEventInterface;
use App\Module\Shared\Domain\Event\DomainEvent;
use App\Module\User\Domain\User;

class UserSoftDeletedDomainEvent extends DomainEvent implements AsyncDomainEventInterface
{
    private User $user;

    /**
     * @param User $user
     * @param string|null $eventId
     * @param \DateTime|null $occurredOn
     */
    public function __construct(User $user, string $eventId = null, \DateTime $occurredOn = null)
    {
        parent::__construct($eventId, $occurredOn);

        $this->user = $user;
    }

    public static function eventName(): string
    {
        return 'user.soft_deleted';
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

    public function getUser(): User
    {
        return $this->user;
    }
}
