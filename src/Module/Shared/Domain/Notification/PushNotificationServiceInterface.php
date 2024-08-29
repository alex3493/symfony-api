<?php
declare(strict_types=1);

namespace App\Module\Shared\Domain\Notification;

interface PushNotificationServiceInterface
{
    public function send(string $channel, string $event, array $payload): void;
}
