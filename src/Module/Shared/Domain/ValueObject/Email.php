<?php
declare(strict_types=1);

namespace App\Module\Shared\Domain\ValueObject;

use App\Module\Shared\Domain\Exception\UnprocessableEntityDomainException;

readonly class Email
{
    /**
     * @param string $value
     * @throws UnprocessableEntityDomainException
     */
    public function __construct(private string $value)
    {
        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new UnprocessableEntityDomainException("Email {$value} is not valid.");
        }
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
