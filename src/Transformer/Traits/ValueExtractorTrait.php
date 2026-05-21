<?php

declare(strict_types = 1);

namespace Ufo\DTO\Transformer\Traits;

use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Helpers\TypeHintResolver;

use function array_key_exists;
use function sprintf;

trait ValueExtractorTrait
{
    /**
     * @throws BadParamException
     */
    protected function requireValue(
        array $payload,
        string $field,
        ?TypeHintResolver $type = null,
    ): mixed
    {
        if (!array_key_exists($field, $payload)) {
            throw new BadParamException(sprintf('Missing required field "%s"', $field));
        }

        $value = $payload[$field];
        if ($type && !$type->matchType($value)) {
            throw new BadParamException(sprintf(
                'Field "%s" must be of type %s, %s given',
                $field,
                $type->value,
                get_debug_type($value),
            ));
        }

        return $value;
    }
}