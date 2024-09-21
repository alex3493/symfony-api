<?php
declare(strict_types=1);

namespace App\Module\Shared\Infrastructure\Persistence\Service;

use App\Module\Shared\Domain\Message\MercureUpdateMessage;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\MessageBusInterface;

abstract readonly class MercureUpdateCapableService
{
    /**
     * @param \Symfony\Component\Messenger\MessageBusInterface $bus
     * @param \Symfony\Bundle\SecurityBundle\Security $security
     */
    protected function __construct(private MessageBusInterface $bus, private Security $security)
    {
    }

    protected abstract function listTopic(): string;

    protected abstract function singleItemTopic(): string;

    /**
     * @param array $data
     * @param string $action
     * @param bool $publishToList
     * @param bool $publishToItem
     * @return void
     */
    protected function publishMercureUpdate(
        array $data, string $action, bool $publishToList = true, bool $publishToItem = false
    ): void {
        $causer = $this->security->getUser()?->getUserIdentifier();

        if ($publishToList) {
            $listMessage = new MercureUpdateMessage($this->listTopic(), [
                'item' => $data,
                'action' => $action,
                'causer' => $causer,
            ]);
            $this->bus->dispatch($listMessage);
        }
        if ($publishToItem) {
            $itemMessage = new MercureUpdateMessage($this->singleItemTopic().$data['id'], [
                'item' => $data,
                'action' => $action,
                'causer' => $causer,
            ]);
            $this->bus->dispatch($itemMessage);
        }
    }
}
