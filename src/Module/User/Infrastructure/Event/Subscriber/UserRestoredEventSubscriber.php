<?php
declare(strict_types=1);

namespace App\Module\User\Infrastructure\Event\Subscriber;

use App\Module\Shared\Domain\Event\DomainEventSubscriberInterface;
use App\Module\User\Domain\Event\UserRestoredDomainEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class UserRestoredEventSubscriber implements DomainEventSubscriberInterface
{
    /**
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function __invoke(UserRestoredDomainEvent $event): void
    {
        // TODO: Here we can restore related entities.
        $this->logger->info('UserRestoredEventSubscriber triggered', ['event' => $event]);
    }
}
