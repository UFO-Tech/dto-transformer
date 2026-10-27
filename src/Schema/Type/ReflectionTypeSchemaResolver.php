<?php

declare(strict_types=1);

namespace Ufo\DTO\Schema\Type;

use phpDocumentor\Reflection\DocBlock\Tags\Param;
use ReflectionException;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;
use Ufo\DTO\Schema\Meta\ReflectionMetadataProviderInterface;
use Ufo\DTO\Schema\Meta\StrictModeResolverInterface;

class ReflectionTypeSchemaResolver implements TypeSchemaResolverInterface
{
    public function __construct(
        protected StrictModeResolverInterface $strictModeResolver,
        protected ReflectionMetadataProviderInterface $metadataProvider,
    ) {}

    /**
     * @param array<string, string> $namespaces
     * @throws ReflectionException
     */
    public function schema(
        ReflectionProperty|ReflectionParameter|ReflectionType $reflection,
        array $namespaces = [],
    ): ResolvedTypeSchema
    {
        if ($reflection instanceof ReflectionType) {
            return new ResolvedTypeSchema(
                schema: $this->nativeTypeSchema($reflection, $namespaces),
                fromDocblock: false,
                strict: true,
            );
        }

        return $this->fromSource($this->source($reflection), $namespaces);
    }

    /**
     * @throws ReflectionException
     */
    public function source(ReflectionProperty|ReflectionParameter $reflection): TypeSource
    {
        $docType = $this->docType($reflection);

        return new TypeSource(
            docType: $docType,
            nativeType: $reflection->getType(),
            declaringNamespaces: $this->getDeclaringNamespaces($reflection),
            docStrict: $docType !== '' && $this->strictModeResolver->resolve($reflection),
        );
    }

    /**
     * @param array<string, string> $namespaces
     */
    public function fromSource(TypeSource $source, array $namespaces = []): ResolvedTypeSchema
    {
        $namespaces = [...$source->declaringNamespaces, ...$namespaces];

        if ($source->docType !== '') {
            $docSchema = TypeHintResolver::typeDescriptionToJsonSchema($source->docType, $namespaces);
            if ($docSchema) {
                return new ResolvedTypeSchema(schema: $docSchema, fromDocblock: true, strict: $source->docStrict);
            }
        }

        return new ResolvedTypeSchema(
            schema: $this->nativeTypeSchema($source->nativeType, $namespaces),
            fromDocblock: false,
            strict: true,
        );
    }

    /**
     * @param array<string, string> $namespaces
     * @throws ReflectionException
     */
    public function docTypeSchema(
        ReflectionProperty|ReflectionParameter $reflection,
        array $namespaces = [],
    ): array
    {
        $docType = $this->docType($reflection);
        if ($docType === '') {
            return [];
        }

        return TypeHintResolver::typeDescriptionToJsonSchema(
            $docType,
            [...$this->getDeclaringNamespaces($reflection), ...$namespaces],
        );
    }

    /**
     * @param array<string, string> $namespaces
     */
    public function nativeTypeSchema(?ReflectionType $type, array $namespaces = []): array
    {
        return $type === null ? [] : TypeHintResolver::reflectionTypeToSchema($type, $namespaces);
    }

    /**
     * @return array<string, string>
     */
    public function getDeclaringNamespaces(ReflectionProperty|ReflectionParameter $reflection): array
    {
        return $this->metadataProvider->declaringNamespaces($reflection);
    }

    protected function getParamTag(ReflectionParameter $reflection): ?Param
    {
        foreach ($this->metadataProvider->docBlock($reflection->getDeclaringFunction())->getTagsByName('param') as $tag) {
            if ($tag instanceof Param && $tag->getVariableName() === $reflection->getName()) {
                return $tag;
            }
        }

        return null;
    }

    protected function docType(ReflectionProperty|ReflectionParameter $reflection): string
    {
        if ($reflection instanceof ReflectionProperty) {
            return $this->propertyDocType($reflection);
        }

        return $this->parameterDocType($reflection);
    }

    protected function propertyDocType(ReflectionProperty $reflection): string
    {
        $tag = $this->metadataProvider->docBlock($reflection)->getTagsByName('var')[0] ?? null;

        return $tag?->getType() ? (string) $tag->getType() : '';
    }

    protected function parameterDocType(ReflectionParameter $reflection): string
    {
        $tag = $this->getParamTag($reflection);

        return $tag?->getType() ? (string) $tag->getType() : '';
    }
}
