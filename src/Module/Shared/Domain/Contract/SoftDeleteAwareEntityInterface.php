<?php
declare(strict_types=1);

namespace App\Module\Shared\Domain\Contract;

interface SoftDeleteAwareEntityInterface
{
    public function softDelete(): void;

    public function isSoftDeleted(): bool;

    public function restore(): void;
}
