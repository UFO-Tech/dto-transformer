<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Hydrator;

use Ufo\DTO\BaseDTOFromArrayTransformer;
use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Exceptions\InvalidObjectValueException;
use Ufo\DTO\Schema\SchemaNode;
use Ufo\DTO\Schema\Type\TypeHintResolver;
use Ufo\DTO\Transformer\Context\TransformationContext;
use function array_key_exists;
use function is_array;

class AdditionalHydrator extends AbstractHydrator implements SchemaNodeHydratorInterface
{
    public function supports(array $schema): bool
    {
        return $this->type($schema) === TypeHintResolver::OBJECT->value
            && array_key_exists(TypeHintResolver::ADDITIONAL_PROPERTIES, $schema);
    }

    public function resolve(
        array $schema,
        mixed $value,
        TransformationContext $context,
    ): array|object
    {
        $value = $this->objectValue($value);

        if (($schema[TypeHintResolver::ADDITIONAL_PROPERTIES] ?? null) === true) {
            return $this->resolveSmartObject($schema, $value, $context);
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

    public function resolveNode(
        SchemaNode $node,
        mixed $value,
        TransformationContext $context,
    ): array|object
    {
        if ($node->additional === null) {
            return $this->resolve($node->schema, $value, $context);
        }

        $value = $this->objectValue($value);

        foreach ($value as $key => $item) {
            try {
                $value[$key] = $node->additional->resolve($item, $context);
            } catch (BadParamException $e) {
                if ($context->isStrict()) throw $e;
            }
        }

        return $value;
    }

    protected function objectValue(mixed $value): array
    {
        if (!is_array($value)) {
            throw new InvalidObjectValueException('Cannot assign non-array value to object schema');
        }

        return $value;
    }

    protected function resolveSmartObject(
        array $schema,
        array $value,
        TransformationContext $context,
    ): array|object
    {
        if (!isset($value[BaseDTOFromArrayTransformer::DTO_CLASSNAME])) {
            throw new BadParamException('Not all values were transformed: missing smart DTO class name');
        }

        return $this->resolveNestedUncached(
            [
                ...$schema,
                TypeHintResolver::CLASS_FQCN => $value[BaseDTOFromArrayTransformer::DTO_CLASSNAME],
            ],
            $value,
            $context,
        );
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
            if ($context->isStrict()) throw $e;
            return $item;
        }
    }
}
