<?php
declare(strict_types=1);

namespace App\Module\Shared\Domain\Exception;

class UnprocessableEntityDomainException extends DomainException
{
    public function __construct(string $description)
    {
        parent::__construct($description, self::$codes['UNPROCESSABLE_ENTITY']);
    }
}
