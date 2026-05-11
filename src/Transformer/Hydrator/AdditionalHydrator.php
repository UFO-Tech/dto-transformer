<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Hydrator;

use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\VO\TransformationContext;
use function is_array;

class AdditionalHydrator extends AbstractParamHydrator
{
    public function supports(array $schema): bool
    {
        return is_array($schema[TypeHintResolver::ADDITIONAL_PROPERTIES] ?? null);
    }

    public function resolve(
        array $schema,
        mixed $value,
        TransformationContext $context,
    ): array
    {
        if (!is_array($value)) {
            throw new BadParamException('Cannot assign non-array value to object schema');
        }

        foreach ($value as $key => $item) {
            $value[$key] = $this->resolveNestedProperty(
                $schema[TypeHintResolver::ADDITIONAL_PROPERTIES],
                $item,
                $context,
            );
        }

        return $value;
    }

    protected function resolveNestedProperty(
        array $schema,
        mixed $item,
        TransformationContext $context,
    ): mixed
    {
        try {
            return $this->resolveNested(
                $schema,
                $item,
                $context,
            );
        } catch (BadParamException $e) {
            if ($context->strict) throw $e;
            return $item;
        }
    }
}
