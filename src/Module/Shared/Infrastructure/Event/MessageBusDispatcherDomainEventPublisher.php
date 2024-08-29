<?php
declare(strict_types=1);

namespace App\Module\Shared\Infrastructure\Event;

use App\Module\Shared\Domain\Event\DomainEvent;
use App\Module\Shared\Domain\Event\DomainEventPublisherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class MessageBusDispatcherDomainEventPublisher implements DomainEventPublisherInterface
{
    private MessageBusInterface $dispatcher;

    private LoggerInterface $logger;

    public function __construct(MessageBusInterface $dispatcher, LoggerInterface $logger)
    {
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
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
