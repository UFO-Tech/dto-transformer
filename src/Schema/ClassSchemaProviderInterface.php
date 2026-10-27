<?php

declare(strict_types=1);

namespace Ufo\DTO\Schema;

use ReflectionException;
use Ufo\DTO\Schema\Type\ResolvedTypeSchema;

interface ClassSchemaProviderInterface
{
    /**
     * @throws ReflectionException
     */
    public function schema(string $classFQCN): ClassSchema;

    /**
     * @param array<string, string> $namespaces
     * @return array<int, ?ResolvedTypeSchema> за індексом властивості в схемі класу
     */
    public function typeSchemas(ClassSchema $schema, array $namespaces): array;
}
