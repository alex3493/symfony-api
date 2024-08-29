<?php
declare(strict_types=1);

namespace App\Module\Shared\Domain\ValueObject;

use Symfony\Component\Uid\Uuid;

class EntityId
{
    private string $value;

    /**
     * @param string $value
     */
    public function __construct(string $value)
    {
        $this->value = $value;
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
