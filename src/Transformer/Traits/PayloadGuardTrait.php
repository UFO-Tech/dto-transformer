<?php

declare(strict_types = 1);

namespace Ufo\DTO\Transformer\Traits;

use Ufo\DTO\Exceptions\BadParamException;

trait PayloadGuardTrait
{
    protected function requireArray(
        mixed $payload,
        string $message = 'Payload must be array',
    ): array
    {
        if (!is_array($payload)) {
            throw new BadParamException($message);
        }
        return $payload;
    }
}