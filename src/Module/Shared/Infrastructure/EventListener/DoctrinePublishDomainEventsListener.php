<?php
declare(strict_types=1);

namespace App\Module\Shared\Infrastructure\EventListener;

use App\Module\Shared\Domain\Event\DomainEventAwareEntity;
use App\Module\Shared\Domain\Event\DomainEventPublisherInterface;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::onFlush)]
readonly class DoctrinePublishDomainEventsListener
{
    public function __construct(private DomainEventPublisherInterface $publisher)
    {
    }

    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $unitOfWork = $eventArgs->getObjectManager()->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            $this->publishDomainEvent($entity);
        }

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            $this->publishDomainEvent($entity);
        }

        foreach ($unitOfWork->getScheduledEntityDeletions() as $entity) {
            $this->publishDomainEvent($entity);
        }

        foreach ($unitOfWork->getScheduledCollectionDeletions() as $collection) {
            foreach ($collection as $entity) {
                $this->publishDomainEvent($entity);
            }
        }

        foreach ($unitOfWork->getScheduledCollectionUpdates() as $collection) {
            foreach ($collection as $entity) {
                $this->publishDomainEvent($entity);
            }
        }
    }

    private function publishDomainEvent(object $entity): void
    {
        if ($entity instanceof DomainEventAwareEntity && $entity->hasDomainEvents()) {
            $this->publisher->publish(...$entity->pullDomainEvents());
        }
    }
}
