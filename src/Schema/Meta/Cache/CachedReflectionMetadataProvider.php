<?php

declare(strict_types=1);

namespace Ufo\DTO\Schema\Meta\Cache;

use ReflectionClass;
use ReflectionFunctionAbstract;
use ReflectionParameter;
use ReflectionProperty;
use Ufo\DTO\Schema\Meta\DocBlockMetadata;
use Ufo\DTO\Schema\Meta\ReflectionMetadataKeyGenerator;
use Ufo\DTO\Schema\Meta\ReflectionMetadataProviderInterface;
use Ufo\DTO\Schema\Meta\RuntimeReflectionCache;

use function sprintf;

/**
 * Метадані в runtime-кеші; `use`-namespaces класу — ще й у persistent-кеші (їх отримують розбором файлу).
 */
class CachedReflectionMetadataProvider implements ReflectionMetadataProviderInterface
{
    protected const string KEY_FORMAT = '%s%s';
    protected const string CACHE_DECLARING_NAMESPACES_PREFIX = 'reflection_metadata.declaring_namespaces.';
    protected const string RUNTIME_PROPERTIES_PREFIX = 'runtime.properties.';
    protected const string RUNTIME_CONSTRUCTOR_PARAMETERS_PREFIX = 'runtime.constructor_parameters.';
    protected const string RUNTIME_NAMESPACES_PREFIX = 'runtime.namespaces.';
    protected const string RUNTIME_DOC_BLOCK_PREFIX = 'runtime.doc_block.';

    public function __construct(
        protected ReflectionMetadataProviderInterface $inner,
        protected ReflectionMetadataKeyGenerator $keyGenerator,
        protected RuntimeReflectionCache $runtimeCache,
    ) {}

    public function reflectionProperties(ReflectionClass|string $class): array
    {
        return $this->runtimeCache->remember(
            $this->cacheKey(static::RUNTIME_PROPERTIES_PREFIX, $this->keyGenerator->classKey($class)),
            fn (): array => $this->inner->reflectionProperties($class),
        );
    }

    public function constructorParameters(ReflectionClass|string $class): array
    {
        return $this->runtimeCache->remember(
            $this->cacheKey(static::RUNTIME_CONSTRUCTOR_PARAMETERS_PREFIX, $this->keyGenerator->classKey($class)),
            fn (): array => $this->inner->constructorParameters($class),
        );
    }

    public function declaringNamespaces(ReflectionClass|ReflectionProperty|ReflectionParameter $reflection): array
    {
        $class = $this->keyGenerator->declaringClassKey($reflection);
        if ($class === null) {
            return $this->inner->declaringNamespaces($reflection);
        }

        return $this->runtimeCache->remember(
            $this->cacheKey(static::RUNTIME_NAMESPACES_PREFIX, $class),
            fn (): array => $this->runtimeCache->rememberPersistent(
                $this->cacheKey(static::CACHE_DECLARING_NAMESPACES_PREFIX, $class),
                fn (): array => $this->inner->declaringNamespaces($reflection),
            ),
        );
    }

    public function docBlock(ReflectionClass|ReflectionFunctionAbstract|ReflectionProperty $reflection): DocBlockMetadata
    {
        return $this->runtimeCache->remember(
            $this->cacheKey(static::RUNTIME_DOC_BLOCK_PREFIX, $this->keyGenerator->reflectionKey($reflection)),
            fn (): DocBlockMetadata => $this->inner->docBlock($reflection),
        );
    }

    public function attributes(
        ReflectionClass|ReflectionFunctionAbstract|ReflectionParameter|ReflectionProperty $reflection,
        ?string $name = null,
        int $flags = 0,
    ): array
    {
        return $this->inner->attributes($reflection, $name, $flags);
    }

    protected function cacheKey(string $prefix, string $identifier): string
    {
        return sprintf(static::KEY_FORMAT, $prefix, $identifier);
    }
}
