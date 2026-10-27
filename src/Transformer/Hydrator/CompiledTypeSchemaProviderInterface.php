<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Hydrator;

use ReflectionException;
use Ufo\DTO\Schema\ClassSchema;
use Ufo\DTO\Schema\CompiledTypeSchema;

interface CompiledTypeSchemaProviderInterface
{
    /**
     * @param array<string, string> $namespaces
     * @return array<int, ?CompiledTypeSchema> за індексом властивості в схемі класу
     * @throws ReflectionException
     */
    public function typeSchemas(ClassSchema $schema, array $namespaces): array;
}
