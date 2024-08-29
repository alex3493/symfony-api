<?php
declare(strict_types=1);

namespace App\Module\User\Infrastructure\Event\Subscriber;

use App\Module\Shared\Domain\Event\DomainEventSubscriberInterface;
use App\Module\User\Domain\Event\UserSoftDeletedDomainEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UserSoftDeletedEventSubscriber implements DomainEventSubscriberInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function __invoke(UserSoftDeletedDomainEvent $event): void
    {
        // TODO: Here we can soft-delete related entities.
        $this->logger->info('UserSoftDeletedDomainEvent triggered', ['event' => $event]);
    }
}
