<?php
declare(strict_types=1);

namespace App\Module\User\Domain\ValueObject;

use App\Module\Shared\Domain\Exception\UnprocessableEntityDomainException;

class UserRole
{
    const ROLE_USER = 'ROLE_USER';

    const ROLE_ADMIN = 'ROLE_ADMIN';

    private static array $VALID_ROLES = [
        self::ROLE_USER,
        self::ROLE_ADMIN,
    ];

    private string $value;

    /**
     * @param string $value
     * @throws UnprocessableEntityDomainException
     */
    public function __construct(string $value)
    {
        if (! in_array($value, self::$VALID_ROLES, true)) {
            throw new UnprocessableEntityDomainException("Role {$value} is not valid.");
        }
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
