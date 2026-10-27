<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer;

use Ufo\DTO\BaseDTOFromArrayTransformer;
use Ufo\DTO\Exceptions\NotSupportDTOException;
use Ufo\DTO\Transformer\Context\TransformationContext;

use function class_exists;
use function is_string;

/**
 * Клас DTO з ключа `$className` у даних (smart array) з урахуванням namespaces контексту.
 */
class SmartClassResolver
{
    /**
     * Клас із даних, якщо його вдалося визначити, і дані без службового ключа; інакше — переданий клас.
     *
     * @param array<string, mixed> $data
     */
    public function resolveForData(string $classFQCN, array &$data, TransformationContext $context): string
    {
        if (!isset($data[BaseDTOFromArrayTransformer::DTO_CLASSNAME])) {
            return $classFQCN;
        }

        try {
            $classFQCN = $this->resolve($data[BaseDTOFromArrayTransformer::DTO_CLASSNAME], $context);
            unset($data[BaseDTOFromArrayTransformer::DTO_CLASSNAME]);
        } catch (NotSupportDTOException) {}

        return $classFQCN;
    }

    /**
     * @throws NotSupportDTOException
     */
    public function resolve(mixed $classFQCN, TransformationContext $context): string
    {
        if (!is_string($classFQCN)) {
            throw new NotSupportDTOException('Smart DTO class name must be a string');
        }

        if (class_exists($classFQCN)) {
            return $classFQCN;
        }

        $namespace = $context->namespaces()[$classFQCN] ?? $context->namespaces()[BaseDTOFromArrayTransformer::DTO_NS_KEY]
            ?? throw new NotSupportDTOException('Namespace not found for class: ' . $classFQCN);

        $classFQCN = $namespace . '\\' . $classFQCN;
        if (!class_exists($classFQCN)) {
            throw new NotSupportDTOException('Class not exist: ' . $classFQCN);
        }
        return $classFQCN;
    }
}
