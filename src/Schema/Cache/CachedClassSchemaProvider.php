<?php

declare(strict_types=1);

namespace Ufo\DTO\Schema\Cache;

use Ufo\DTO\Helpers\BoundedArray;
use Ufo\DTO\Schema\ClassSchema;
use Ufo\DTO\Schema\ClassSchemaProviderInterface;

use function ltrim;
use function strtolower;

/**
 * Схеми класів у пам'яті процесу з лімітом; схеми типів кешує шар гідраторів.
 */
class CachedClassSchemaProvider implements ClassSchemaProviderInterface
{
    /** Максимум класів у кеші; ім'я класу може прийти з payload, тож кеш обмежено. */
    protected const int SCHEMA_LIMIT = 3000;

    /**
     * @var array<string, ClassSchema> за нормалізованим ім'ям класу
     */
    protected array $schemas = [];

    /**
     * @var array<string, ClassSchema> за ім'ям класу як його передали
     */
    protected array $schemasByName = [];

    public function __construct(
        protected ClassSchemaProviderInterface $inner,
    ) {}

    public function schema(string $classFQCN): ClassSchema
    {
        return $this->schemasByName[$classFQCN]
            ?? BoundedArray::put($this->schemasByName, $classFQCN, $this->normalizedSchema($classFQCN), static::SCHEMA_LIMIT);
    }

    protected function normalizedSchema(string $classFQCN): ClassSchema
    {
        $key = strtolower(ltrim($classFQCN, '\\'));

        return $this->schemas[$key] ?? BoundedArray::put($this->schemas, $key, $this->inner->schema($classFQCN), static::SCHEMA_LIMIT);
    }

    public function typeSchemas(ClassSchema $schema, array $namespaces): array
    {
        return $this->inner->typeSchemas($schema, $namespaces);
    }
}
