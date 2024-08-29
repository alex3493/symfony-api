<?php
declare(strict_types=1);

namespace App\Module\Shared\Domain\Exception;

/**
 * Custom validation exception.
 */
class FormValidationException extends DomainException
{
    private array $errors;

    public function __construct(string $description, array $errors = [])
    {
        parent::__construct($description, self::$codes['UNPROCESSABLE_ENTITY']);

        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
