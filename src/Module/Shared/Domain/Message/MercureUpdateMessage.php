<?php
declare(strict_types=1);

namespace App\Module\Shared\Domain\Message;

readonly class MercureUpdateMessage implements AsyncMessageInterface
{
    /**
     * @param string $topic
     * @param array $payload
     */
    public function __construct(
        private string $topic, private array $payload
    ) {
    }

    public function getTopic(): string
    {
        return $this->topic;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }
}
