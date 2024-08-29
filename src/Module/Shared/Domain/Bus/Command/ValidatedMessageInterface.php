<?php
declare(strict_types=1);

namespace App\Module\Shared\Domain\Bus\Command;

interface ValidatedMessageInterface
{
    /**
     * We are using Messenger validation middleware in command bus.
     * If command validation fails we need validation context, so that
     * our catch-all kernel exception listener can produce a JSON
     * response that follows domain validation exception pattern.
     *
     * @return string
     */
    public function validationContext(): string;
}
