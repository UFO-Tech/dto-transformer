<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Type;

use phpDocumentor\Reflection\DocBlock\Tags\Param;
use ReflectionException;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;
use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\Interfaces\Meta\ReflectionMetadataKeyGeneratorInterface;
use Ufo\DTO\Interfaces\Meta\ReflectionMetadataProviderInterface;
use Ufo\DTO\Interfaces\Meta\RuntimeReflectionCacheInterface;
use Ufo\DTO\Interfaces\Meta\StrictModeResolverInterface;
use Ufo\DTO\Interfaces\Meta\TypeSchemaResolverInterface;
use Ufo\DTO\VO\ResolvedTypeSchemaVO;

class ReflectionTypeSchemaResolver implements TypeSchemaResolverInterface
{
    protected const string KEY_FORMAT = '%s%s';
    protected const string CACHE_TYPE_SCHEMA_PREFIX = 'type_schema.';
    protected const string CACHE_DOC_TYPE_SCHEMA_PREFIX = 'doc_type_schema.';
    protected const string CACHE_NATIVE_TYPE_SCHEMA_PREFIX = 'native_type_schema.';
    protected const string CACHE_SCHEMA_KEY = 'schema';
    protected const string CACHE_FROM_DOCBLOCK_KEY = 'fromDocblock';
    protected const string CACHE_STRICT_KEY = 'strict';

    public function __construct(
        protected StrictModeResolverInterface $strictModeResolver,
        protected ReflectionMetadataProviderInterface $metadataProvider,
        protected ReflectionMetadataKeyGeneratorInterface $keyGenerator,
        protected RuntimeReflectionCacheInterface $runtimeCache,
    ) {}

    /**
     * @param array<string, string> $namespaces
     * @throws ReflectionException
     */
    public function schema(
        ReflectionProperty|ReflectionParameter|ReflectionType $reflection,
        array $namespaces = [],
    ): ResolvedTypeSchemaVO
    {
        // todo refactor this code

        if ($reflection instanceof ReflectionType) {
            return new ResolvedTypeSchemaVO(
                schema: $this->nativeTypeSchema($reflection, $namespaces),
                fromDocblock: false,
                strict: true,
            );
        }

        $cacheKey = $this->keyGenerator->reflectionSchemaKey($reflection, $namespaces);
        if (!$cacheKey) {
            return new ResolvedTypeSchemaVO(
                schema: $this->nativeTypeSchema($reflection, $namespaces),
                fromDocblock: false,
                strict: true,
            );
        }

        $runtimeKey = $this->cacheKey(static::CACHE_TYPE_SCHEMA_PREFIX, $cacheKey);
        return $this->runtimeCache->rememberPersistent(
            $runtimeKey,
            function () use ($reflection, $namespaces): array {
                $docSchema = $this->docTypeSchema($reflection, $namespaces);
                if ($docSchema) {
                    return [
                        static::CACHE_SCHEMA_KEY => $docSchema,
                        static::CACHE_FROM_DOCBLOCK_KEY => true,
                        static::CACHE_STRICT_KEY => $this->strictModeResolver->resolve($reflection),
                    ];
                }

                return [
                    static::CACHE_SCHEMA_KEY => $this->nativeTypeSchema($reflection->getType(), [
                        ...$this->getDeclaringNamespaces($reflection),
                        ...$namespaces,
                    ]),
                    static::CACHE_FROM_DOCBLOCK_KEY => false,
                    static::CACHE_STRICT_KEY => true,
                ];
            },
            fn (array|ResolvedTypeSchemaVO $metadata): ResolvedTypeSchemaVO => $this->createResolvedTypeSchema($metadata),
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
        $cacheKey = $this->keyGenerator->reflectionSchemaKey($reflection, $namespaces);
        $runtimeKey = $this->cacheKey(static::CACHE_DOC_TYPE_SCHEMA_PREFIX, (string) $cacheKey);

        return $this->runtimeCache->rememberPersistent($runtimeKey, function () use ($reflection, $namespaces): array {
            $docType = '';

            if ($reflection instanceof ReflectionProperty) {
                $tag = $this->metadataProvider->docBlock($reflection)->getTagsByName('var')[0] ?? null;
                $docType = $tag?->getType() ? (string) $tag->getType() : '';
            }

            if ($reflection instanceof ReflectionParameter) {
                $tag = $this->getParamTag($reflection);
                $docType = $tag?->getType() ? (string) $tag->getType() : $docType;
            }

            if ($docType === '') {
                return [];
            }

            return TypeHintResolver::typeDescriptionToJsonSchema((string) $docType, [
                ...$this->getDeclaringNamespaces($reflection),
                ...$namespaces,
            ]);
        });
    }

    /**
     * @param array<string, string> $namespaces
     * @throws ReflectionException
     */
    public function nativeTypeSchema(?ReflectionType $type, array $namespaces = []): array
    {
        if ($type === null) {
            return [];
        }

        $runtimeKey = $this->cacheKey(static::CACHE_NATIVE_TYPE_SCHEMA_PREFIX, $this->keyGenerator->nativeTypeKey($type, $namespaces));

        return $this->runtimeCache->rememberPersistent(
            $runtimeKey,
            static fn (): array => TypeHintResolver::reflectionTypeToSchema($type, $namespaces),
        );
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
        foreach ($this->metadataProvider->parameterMetadata($reflection)->declaringFunctionDocBlock->getTagsByName('param') as $tag) {
            if ($tag instanceof Param && $tag->getVariableName() === $reflection->getName()) {
                return $tag;
            }
        }

        return null;
    }

    /**
     * @param array{schema?: array, fromDocblock?: bool, strict?: bool}|ResolvedTypeSchemaVO $metadata
     */
    protected function createResolvedTypeSchema(array|ResolvedTypeSchemaVO $metadata): ResolvedTypeSchemaVO
    {
        if ($metadata instanceof ResolvedTypeSchemaVO) {
            return $metadata;
        }

        return new ResolvedTypeSchemaVO(
            schema: $metadata[static::CACHE_SCHEMA_KEY] ?? [],
            fromDocblock: (bool) ($metadata[static::CACHE_FROM_DOCBLOCK_KEY] ?? false),
            strict: (bool) ($metadata[static::CACHE_STRICT_KEY] ?? true),
        );
    }

    protected function cacheKey(string $prefix, string $identifier): string
    {
        return sprintf(static::KEY_FORMAT, $prefix, $identifier);
    }
}
