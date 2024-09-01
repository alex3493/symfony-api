<?php
declare(strict_types=1);

namespace App\Module\Shared\Domain\Exception;

use ReflectionClass;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * Symfony core validator exception.
 */
class ValidationException extends DomainException
{
    protected array $errors = [];

    /**
     * @param \Symfony\Component\Validator\ConstraintViolationList $violations
     * @param string|null $context
     * @throws \ReflectionException
     */
    public function __construct(ConstraintViolationList $violations, ?string $context = null)
    {
        // Default code.
        $code = DomainException::$codes['UNPROCESSABLE_ENTITY'];

        foreach ($violations as $violation) {
            $class = new ReflectionClass($violation->getRoot());
            $context = $context ?? $class->getShortName();

            if ($violation->getConstraint() instanceof UniqueEntity) {
                // If at least one of violations is caused by duplicate entry, we update the code.
                $code = DomainException::$codes['CONFLICT'];
            }

            // We group errors by context and property name.
            if (isset($this->errors[$context.'::'.$violation->getPropertyPath()]['errors'])) {
                $this->errors[$context.'::'.$violation->getPropertyPath()]['errors'][] = $violation->getMessage();
            } else {
                $this->errors[$context.'::'.$violation->getPropertyPath()] = [
                    'property' => $violation->getPropertyPath(),
                    'errors' => [$violation->getMessage()],
                    'context' => $context,
                ];
            }
        }

        $this->errors = array_values($this->errors);

        parent::__construct('Validation failed.', $code);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
