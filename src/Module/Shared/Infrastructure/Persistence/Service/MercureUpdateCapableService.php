<?php

namespace App\Module\Shared\Infrastructure\Persistence\Service;

use App\Module\Shared\Domain\Message\MercureUpdateMessage;
use Symfony\Component\Messenger\MessageBusInterface;

abstract readonly class MercureUpdateCapableService
{
    protected function __construct(private MessageBusInterface $bus)
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
        if ($publishToList) {
            $listMessage = new MercureUpdateMessage($this->listTopic(), [
                'item' => $data,
                'action' => $action,
            ]);
            $this->bus->dispatch($listMessage);
        }
        if ($publishToItem) {
            $itemMessage = new MercureUpdateMessage($this->singleItemTopic().$data['id'], [
                'item' => $data,
                'action' => $action,
            ]);
            $this->bus->dispatch($itemMessage);
        }
    }
}
