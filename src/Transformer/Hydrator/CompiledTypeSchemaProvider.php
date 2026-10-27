<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Hydrator;

use Ufo\DTO\Schema\ClassSchema;
use Ufo\DTO\Schema\ClassSchemaProviderInterface;
use Ufo\DTO\Schema\CompiledTypeSchema;
use Ufo\DTO\Schema\Type\ResolvedTypeSchema;

/**
 * Схеми типів властивостей класу разом із вузлами свого ланцюга гідраторів; без кешу.
 */
class CompiledTypeSchemaProvider implements CompiledTypeSchemaProviderInterface
{
    public function __construct(
        protected ClassSchemaProviderInterface $schemaProvider,
        protected HydratorChainInterface $hydratorChain,
    ) {}

    public function typeSchemas(ClassSchema $schema, array $namespaces): array
    {
        $compiled = [];
        foreach ($this->schemaProvider->typeSchemas($schema, $namespaces) as $index => $resolved) {
            $compiled[$index] = $resolved === null ? null : $this->compile($resolved);
        }

        return $compiled;
    }

    protected function compile(ResolvedTypeSchema $resolved): CompiledTypeSchema
    {
        return new CompiledTypeSchema(
            resolved: $resolved,
            node: $resolved->schema !== [] && $this->hydratorChain instanceof HydratorChain
                ? $this->hydratorChain->node($resolved->schema)
                : null,
        );
    }
}
