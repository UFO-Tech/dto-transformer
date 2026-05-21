<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Hydrator;

use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Exceptions\InvalidCollectionValueException;
use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\VO\TransformationContext;
use function is_array;

class ArrayItemsHydrator extends AbstractParamHydrator
{
    public function supports(array $schema): bool
    {
        return
            $this->type($schema) === TypeHintResolver::ARRAY->value
            && isset($schema[TypeHintResolver::ITEMS]);
    }

    public function resolve(
        array $schema,
        mixed $value,
        TransformationContext $context,
    ): array
    {
        if (!is_array($value)) {
            throw new InvalidCollectionValueException(
                'Cannot assign non-array value to array schema',
            );
        }

        foreach ($value as $key => $item) {
            $value[$key] = $this->resolveNestedItem(
                $schema[TypeHintResolver::ITEMS],
                $item,
                $context,
            );
        }

        return $value;
    }

    protected function resolveNestedItem(
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
            if ($context->isStrict()) throw $e;
            return $item;
        }
    }
}
