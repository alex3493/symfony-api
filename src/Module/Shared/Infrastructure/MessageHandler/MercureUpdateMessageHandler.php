<?php
declare(strict_types=1);

namespace App\Module\Shared\Infrastructure\MessageHandler;

use App\Module\Shared\Domain\Message\MercureUpdateMessage;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

readonly class MercureUpdateMessageHandler
{
    /**
     * @param \Symfony\Component\Mercure\HubInterface $hub
     */
    public function __construct(private HubInterface $hub)
    {
    }

    public function __invoke(MercureUpdateMessage $message): void
    {
        $update = new Update($message->getTopic(), json_encode($message->getPayload()), true);

        $this->hub->publish($update);
    }
}
