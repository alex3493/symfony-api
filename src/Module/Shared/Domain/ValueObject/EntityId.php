<?php
declare(strict_types=1);

namespace App\Module\Shared\Domain\ValueObject;

use Symfony\Component\Uid\Uuid;

readonly class EntityId
{
    /**
     * @param string $value
     */
    private function __construct(private string $value)
    {
    }

    public static function create(): static
    {
        return new static((string) Uuid::v7());
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
