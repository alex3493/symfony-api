<?php
declare(strict_types=1);

namespace App\Module\Shared\Domain\Exception;

abstract class DomainException extends \Exception
{
    public static array $codes = [
        'BAD_REQUEST' => 400,
        'NOT_FOUND' => 404,
        'UNPROCESSABLE_ENTITY' => 422,
        'UNAUTHORIZED' => 401,
        'FORBIDDEN' => 403,
        'CONFLICT' => 409,
    ];
}
