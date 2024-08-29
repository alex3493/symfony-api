<?php
declare(strict_types=1);

namespace App\Module\Shared\Domain\Exception;

class NotFoundDomainException extends DomainException
{
    public function __construct(string $description)
    {
        parent::__construct($description, self::$codes['NOT_FOUND']);
    }
}
