<?php

declare(strict_types=1);

namespace Ufo\DTO\Schema\Cache;

use ReflectionClass;
use Symfony\Contracts\Cache\CacheInterface;
use Ufo\DTO\Schema\ClassSchema;
use Ufo\DTO\Schema\ClassSchemaDescriberInterface;

use function md5;

/**
 * Описи схем у persistent-кеші між процесами; ключ — ім'я класу, для схем типів ще й відбиток namespaces.
 */
class CachedClassSchemaDescriber implements ClassSchemaDescriberInterface
{
    protected const string CACHE_PREFIX = 'class_schema.';
    protected const string TYPE_SCHEMAS_SEGMENT = '.types.';

    public function __construct(
        protected ClassSchemaDescriberInterface $inner,
        protected CacheInterface $persistentCache,
    ) {}

    public function describe(ReflectionClass $reflectionClass): array
    {
        return $this->persistentCache->get(
            $this->classKey($reflectionClass),
            fn (mixed ...$_): array => $this->inner->describe($reflectionClass),
        );
    }

    public function redescribe(ReflectionClass $reflectionClass): array
    {
        $key = $this->classKey($reflectionClass);
        $this->persistentCache->delete($key);

        return $this->persistentCache->get($key, fn (mixed ...$_): array => $this->inner->redescribe($reflectionClass));
    }

    public function describeTypeSchemas(ClassSchema $schema, array $namespaces): array
    {
        return $this->persistentCache->get(
            md5(static::CACHE_PREFIX . $schema->reflection->getName() . static::TYPE_SCHEMAS_SEGMENT . md5($schema->namespacesFingerprint($namespaces))),
            fn (mixed ...$_): array => $this->inner->describeTypeSchemas($schema, $namespaces),
        );
    }

    protected function classKey(ReflectionClass $reflectionClass): string
    {
        return md5(static::CACHE_PREFIX . $reflectionClass->getName());
    }
}
