<?php

namespace App\Module\User\Infrastructure\Event\Subscriber;

use App\Module\Shared\Domain\Event\DomainEventSubscriberInterface;
use App\Module\User\Domain\Event\UserCreatedDomainEvent;
use Psr\Log\LoggerInterface;

readonly class UserCreatedEventSubscriber implements DomainEventSubscriberInterface
{
    /**
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(UserCreatedDomainEvent $event): void
    {
        // TODO: Here we can send welcome email.
        $this->logger->info('UserCreatedDomainEvent triggered', ['event' => $event]);
    }
}
