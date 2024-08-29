<?php
declare(strict_types=1);

namespace App\Module\Shared\Domain\Bus\Command;

use App\Module\Shared\Domain\Bus\Contract\CommandResponse;

interface CommandBus
{
    /**
     * @param \App\Module\Shared\Domain\Bus\Command\Command|\App\Module\Shared\Domain\Bus\Command\AsyncCommand $command
     * @return \App\Module\Shared\Domain\Bus\Contract\CommandResponse|null
     */
    public function dispatch(Command|AsyncCommand $command): ?CommandResponse;
}
