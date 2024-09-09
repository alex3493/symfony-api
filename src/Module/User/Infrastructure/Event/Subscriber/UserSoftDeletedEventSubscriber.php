<?php
declare(strict_types=1);

namespace App\Module\User\Infrastructure\Event\Subscriber;

use App\Module\Shared\Domain\Event\DomainEventSubscriberInterface;
use App\Module\User\Domain\Event\UserSoftDeletedDomainEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class UserSoftDeletedEventSubscriber implements DomainEventSubscriberInterface
{
    /**
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function __invoke(UserSoftDeletedDomainEvent $event): void
    {
        // TODO: Here we can soft-delete related entities.
        $this->logger->info('UserSoftDeletedDomainEvent triggered', ['event' => $event]);
    }
}
