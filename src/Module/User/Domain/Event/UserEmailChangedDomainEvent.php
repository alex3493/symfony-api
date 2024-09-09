<?php
declare(strict_types=1);

namespace App\Module\User\Domain\Event;

use App\Module\Shared\Domain\Event\AsyncDomainEventInterface;
use App\Module\Shared\Domain\Event\DomainEvent;
use App\Module\User\Domain\User;

class UserEmailChangedDomainEvent extends DomainEvent implements AsyncDomainEventInterface
{
    /**
     * @param User $user
     * @param string $oldEmail
     * @param string $newEmail
     * @param string|null $eventId
     * @param \DateTime|null $occurredOn
     */
    public function __construct(
        private readonly User $user, private readonly string $oldEmail, private readonly string $newEmail,
        string $eventId = null, \DateTime $occurredOn = null
    ) {
        parent::__construct($eventId, $occurredOn);
    }

    public static function eventName(): string
    {
        return 'user.restored';
    }

    public function toPrimitives(): array
    {
        return [
            'user' => $this->user,
            'oldEmail' => $this->oldEmail,
            'newEmail' => $this->newEmail,
        ];
    }

    public static function fromPrimitives(array $body, string $eventId, \DateTime $occurredOn): DomainEvent
    {
        return new self($body['user'], $body['oldEmail'], $body['newEmail'], $eventId, $occurredOn);
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getOldEmail(): string
    {
        return $this->oldEmail;
    }

    public function getNewEmail(): string
    {
        return $this->newEmail;
    }
}
