<?php
declare(strict_types=1);

namespace App\Module\Shared\Domain\Message;

class MercureUpdateMessage implements AsyncMessageInterface
{
    private string $topic;

    private array $payload;

    public function __construct(string $topic, array $payload)
    {
        $this->topic = $topic;
        $this->payload = $payload;
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
