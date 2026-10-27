<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Hydrator\Cache;

use Ufo\DTO\Helpers\BoundedArray;
use Ufo\DTO\Schema\ClassSchema;
use Ufo\DTO\Transformer\Hydrator\CompiledTypeSchemaProviderInterface;
use WeakMap;

/**
 * Скомпільовані схеми типів у пам'яті процесу: за схемою класу й відбитком namespaces, з лімітом наборів.
 */
class CachedCompiledTypeSchemaProvider implements CompiledTypeSchemaProviderInterface
{
    /** Максимум додаткових наборів схем типів на клас; namespaces викликача можуть змінюватися між викликами. */
    protected const int TYPE_SCHEMA_SETS_LIMIT = 32;

    /**
     * @var WeakMap<ClassSchema, TypeSchemaSets>
     */
    protected WeakMap $typeSchemaSets;

    public function __construct(
        protected CompiledTypeSchemaProviderInterface $inner,
    ) {
        $this->typeSchemaSets = new WeakMap();
    }

    public function typeSchemas(ClassSchema $schema, array $namespaces): array
    {
        $fingerprint = $schema->namespacesFingerprint($namespaces);
        $sets = $this->typeSchemaSets[$schema] ??= new TypeSchemaSets();

        if ($sets->fingerprint === $fingerprint) {
            return $sets->schemas;
        }

        if ($sets->fingerprint === null) {
            $sets->fingerprint = $fingerprint;

            return $sets->schemas = $this->inner->typeSchemas($schema, $namespaces);
        }

        return $sets->sets[$fingerprint]
            ?? BoundedArray::put($sets->sets, $fingerprint, $this->inner->typeSchemas($schema, $namespaces), static::TYPE_SCHEMA_SETS_LIMIT);
    }
}
