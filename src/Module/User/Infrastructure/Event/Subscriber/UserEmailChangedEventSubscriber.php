<?php
declare(strict_types=1);

namespace App\Module\User\Infrastructure\Event\Subscriber;

use App\Module\Shared\Domain\Event\DomainEventSubscriberInterface;
use App\Module\User\Domain\Event\UserEmailChangedDomainEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class UserEmailChangedEventSubscriber implements DomainEventSubscriberInterface
{
    /**
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function __invoke(UserEmailChangedDomainEvent $event): void
    {
        // TODO: Here we can require email confirmation.
        $this->logger->info('UserEmailChangedDomainEvent triggered', ['event' => $event]);
    }
}
