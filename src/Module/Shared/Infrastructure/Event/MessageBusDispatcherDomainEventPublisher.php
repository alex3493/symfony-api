<?php
declare(strict_types=1);

namespace App\Module\Shared\Infrastructure\Event;

use App\Module\Shared\Domain\Event\DomainEvent;
use App\Module\Shared\Domain\Event\DomainEventPublisherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class MessageBusDispatcherDomainEventPublisher implements DomainEventPublisherInterface
{
    /**
     * @param \Symfony\Component\Messenger\MessageBusInterface $dispatcher
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        private MessageBusInterface $dispatcher, private LoggerInterface $logger
    ) {
    }

    /**
     * @param array<DomainEvent> $domainEvents
     * @return void
     */
    public function publish(DomainEvent ...$domainEvents): void
    {
        foreach ($domainEvents as $event) {
            $this->logger->debug('MessageBusDispatcherDomainEventPublisher', [
                'name' => $event::eventName(),
            ]);
            $this->dispatcher->dispatch($event);
        }
    }
}
