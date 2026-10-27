<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Hydrator;

use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Exceptions\InvalidCollectionValueException;
use Ufo\DTO\Schema\SchemaNode;
use Ufo\DTO\Schema\Type\TypeHintResolver;
use Ufo\DTO\Transformer\Context\TransformationContext;
use function is_array;

class ArrayItemsHydrator extends AbstractHydrator implements SchemaNodeHydratorInterface
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
        $value = $this->arrayValue($value);

        foreach ($value as $key => $item) {
            $value[$key] = $this->resolveNestedItem(
                $schema[TypeHintResolver::ITEMS],
                $item,
                $context,
            );
        }

        return $value;
    }

    public function resolveNode(
        SchemaNode $node,
        mixed $value,
        TransformationContext $context,
    ): array
    {
        if ($node->items === null) {
            return $this->resolve($node->schema, $value, $context);
        }

        $value = $this->arrayValue($value);

        foreach ($value as $key => $item) {
            try {
                $value[$key] = $node->items->resolve($item, $context);
            } catch (BadParamException $e) {
                if ($context->isStrict()) throw $e;
            }
        }

        return $value;
    }

    protected function arrayValue(mixed $value): array
    {
        if (!is_array($value)) {
            throw new InvalidCollectionValueException(
                'Cannot assign non-array value to array schema',
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
