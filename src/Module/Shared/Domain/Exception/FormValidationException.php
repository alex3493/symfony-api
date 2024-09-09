<?php
declare(strict_types=1);

namespace App\Module\Shared\Domain\Exception;

/**
 * Custom validation exception.
 */
class FormValidationException extends DomainException
{
    public function __construct(
        string $description, private readonly array $errors = []
    ) {
        parent::__construct($description, self::$codes['UNPROCESSABLE_ENTITY']);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
