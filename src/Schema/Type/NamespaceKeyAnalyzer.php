<?php

declare(strict_types=1);

namespace Ufo\DTO\Schema\Type;

use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;
use Ufo\DTO\BaseDTOFromArrayTransformer;
use Ufo\DTO\Schema\Meta\ReflectionMetadataProviderInterface;

use function array_keys;
use function explode;
use function implode;
use function ltrim;
use function method_exists;
use function preg_match_all;

/**
 * Ключі namespaces, які резолвер схеми може прочитати для типу властивості чи параметра.
 */
class NamespaceKeyAnalyzer
{
    protected const string TYPE_NAME_PATTERN = '/[A-Za-z_\x80-\xff\\\\][A-Za-z0-9_\x80-\xff\\\\]*/';
    protected const array DOC_TYPE_TAGS = ['var', 'param'];

    public function __construct(
        protected ReflectionMetadataProviderInterface $metadataProvider,
    ) {}

    /**
     * @return list<string>
     */
    public function keys(ReflectionProperty|ReflectionParameter $reflection): array
    {
        $types = $this->namespaceDependentNativeTypes($reflection->getType());

        $docBlock = $reflection instanceof ReflectionProperty
            ? $this->metadataProvider->docBlock($reflection)
            : $this->metadataProvider->docBlock($reflection->getDeclaringFunction());

        foreach (static::DOC_TYPE_TAGS as $tagName) {
            foreach ($docBlock->getTagsByName($tagName) as $tag) {
                if (method_exists($tag, 'getType') && $tag->getType() !== null) {
                    $types[] = (string) $tag->getType();
                }
            }
        }

        $keys = [];
        preg_match_all(static::TYPE_NAME_PATTERN, implode(' ', $types), $matches);

        foreach ($matches[0] as $typeName) {
            $typeName = ltrim($typeName, '\\');
            if ($typeName === '' || TypeHintResolver::normalize($typeName) !== TypeHintResolver::OBJECT->value) continue;

            $keys[$typeName] = true;
            foreach (explode('\\', $typeName) as $segment) {
                $keys[$segment] = true;
            }
        }

        if ($keys !== []) {
            $keys = [BaseDTOFromArrayTransformer::DTO_NS_KEY => true] + $keys;
        }

        return array_keys($keys);
    }

    /**
     * Нативні типи, схема яких залежить від namespaces: не вбудовані, не існуючі класи й не enum.
     *
     * @return list<string>
     */
    protected function namespaceDependentNativeTypes(?ReflectionType $type): array
    {
        if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
            $names = [];
            foreach ($type->getTypes() as $innerType) {
                $names = [...$names, ...$this->namespaceDependentNativeTypes($innerType)];
            }

            return $names;
        }

        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return [];
        }

        $name = $type->getName();

        return TypeHintResolver::isRealClass($name) || TypeHintResolver::isEnum($name) ? [] : [$name];
    }
}
