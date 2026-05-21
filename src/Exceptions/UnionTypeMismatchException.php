<?php

declare(strict_types = 1);

namespace Ufo\DTO\Exceptions;

use Throwable;

class UnionTypeMismatchException extends BadParamException
{
    /**
     * @param array<Throwable> $errors
     */
    public function __construct(
        array $errors = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            'Value does not match any union type branch.',
            previous: $previous ?? $errors[array_key_last($errors)] ?? null,
        );
    }
}
