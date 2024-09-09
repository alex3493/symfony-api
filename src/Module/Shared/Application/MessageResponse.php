<?php
declare(strict_types=1);

namespace App\Module\Shared\Application;

use App\Module\Shared\Domain\Bus\Contract\CommandResponse;

class MessageResponse implements CommandResponse
{
    /**
     * @param string $message
     */
    public function __construct(public string $message)
    {
    }
}
