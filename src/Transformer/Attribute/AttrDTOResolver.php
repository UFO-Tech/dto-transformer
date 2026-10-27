<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Attribute;

use Closure;
use ReflectionParameter;
use ReflectionProperty;
use Throwable;
use Ufo\DTO\Attributes\AttrDTO;
use Ufo\DTO\Contract\StaticDTOFromArrayTransformerInterface;
use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Exceptions\NotSupportDTOException;
use Ufo\DTO\Transformer\Context\TransformationContext;
use Ufo\DTO\Transformer\Converter\EnumConverter;
use Ufo\DTO\Transformer\DTOFromContextTransformerInterface;

use function get_debug_type;
use function is_a;
use function is_iterable;
use function sprintf;

/**
 * Значення властивості з `#[AttrDTO]`: `null`, один DTO або enum, колекція.
 */
class AttrDTOResolver
{
    /**
     * @throws BadParamException
     * @throws NotSupportDTOException
     */
    public function resolve(
        AttrDTO $attribute,
        mixed $value,
        ReflectionProperty|ReflectionParameter $reflection,
        DTOFromContextTransformerInterface $transformer,
        TransformationContext $context,
    ): mixed
    {
        if ($value === null) {
            return $this->resolveNull($reflection);
        }

        if ($attribute->isCollection()) {
            return $this->resolveCollection($attribute, $value, $transformer, $context);
        }

        return $this->transformation($attribute, $transformer, $context)($value);
    }

    /**
     * @throws BadParamException
     */
    protected function resolveNull(ReflectionProperty|ReflectionParameter $reflection): null
    {
        if ($reflection->getType()?->allowsNull() ?? true) {
            return null;
        }

        throw new BadParamException(sprintf('Cannot assign null to non-nullable %s', $reflection->getName()));
    }

    /**
     * @throws BadParamException
     */
    protected function resolveCollection(
        AttrDTO $attribute,
        mixed $value,
        DTOFromContextTransformerInterface $transformer,
        TransformationContext $context,
    ): mixed
    {
        if (!is_iterable($value)) {
            if ($attribute->isStrict()) {
                throw new BadParamException(sprintf('Collection of %s expects an iterable, %s given', $attribute->dtoFQCN, get_debug_type($value)));
            }

            return $value;
        }

        $result = [];
        $transform = null;
        foreach ($value as $key => $item) {
            try {
                $transform ??= $this->transformation($attribute, $transformer, $context);
                $result[$key] = $transform($item);
            } catch (Throwable $e) {
                if ($attribute->isStrict()) throw $e;
                $result[$key] = $item;
            }
        }

        return $result;
    }

    /**
     * Перетворення одного значення; все, що не залежить від значення, обчислено заздалегідь.
     *
     * @return Closure(mixed): object
     * @throws NotSupportDTOException
     */
    protected function transformation(
        AttrDTO $attribute,
        DTOFromContextTransformerInterface $transformer,
        TransformationContext $context,
    ): Closure
    {
        $dtoFQCN = $attribute->dtoFQCN;

        if ($attribute->isEnum()) {
            return static fn (mixed $value): object => EnumConverter::toEnum($dtoFQCN, $value);
        }

        $renameKeys = $attribute->renameKeys();
        $context = $context->withContext($attribute->context);

        if ($customTransformer = $attribute->transformerFQCN()) {
            return $this->customTransformation($customTransformer, $dtoFQCN, $renameKeys, $context->namespaces());
        }

        return static fn (mixed $value): object => $transformer->transformFromContext($dtoFQCN, $value, $context, $renameKeys);
    }

    /**
     * @param array<string, string|null> $renameKeys
     * @param array<string, string> $namespaces
     * @return Closure(mixed): object
     * @throws NotSupportDTOException
     */
    protected function customTransformation(string $customTransformer, string $dtoFQCN, array $renameKeys, array $namespaces): Closure
    {
        if (!is_a($customTransformer, StaticDTOFromArrayTransformerInterface::class, true)) {
            throw new NotSupportDTOException(sprintf(
                'Transformer %s must implement %s',
                $customTransformer,
                StaticDTOFromArrayTransformerInterface::class,
            ));
        }

        if (!$customTransformer::isSupportClass($dtoFQCN)) {
            throw new NotSupportDTOException($customTransformer . ' is not support transform for ' . $dtoFQCN);
        }

        return static fn (mixed $value): object => $customTransformer::fromArray($dtoFQCN, $value, $renameKeys, $namespaces);
    }
}
