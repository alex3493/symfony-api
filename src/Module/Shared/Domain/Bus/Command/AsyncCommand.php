<?php
declare(strict_types=1);

namespace App\Module\Shared\Domain\Bus\Command;

interface AsyncCommand
{
    /**
     * Async command will probably create or update entities, and we cannot
     * rely on datetime "now" when the command code is executed.
     * This method should return datetime when the command was added to
     * async queue, so we can use it as "createdAt" or "updatedAt" value
     * in affected entities.
     *
     * @return \DateTime
     */
    public function issuedAt(): \DateTime;
}
