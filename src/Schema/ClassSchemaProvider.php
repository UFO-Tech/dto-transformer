<?php

declare(strict_types=1);

namespace Ufo\DTO\Schema;

use LogicException;
use ReflectionClass;

/**
 * Схеми класів з описів без кешу; описи кешує {@see Cache\CachedClassSchemaDescriber}, схеми — {@see Cache\CachedClassSchemaProvider}.
 */
class ClassSchemaProvider implements ClassSchemaProviderInterface
{
    public function __construct(
        protected ClassSchemaDescriberInterface $describer,
        protected ClassSchemaFactory $factory,
    ) {}

    public function schema(string $classFQCN): ClassSchema
    {
        $reflectionClass = new ReflectionClass($classFQCN);

        return $this->factory->fromDescription($reflectionClass, $this->describer->describe($reflectionClass))
            ?? $this->factory->fromDescription($reflectionClass, $this->describer->redescribe($reflectionClass))
            ?? throw new LogicException('Class schema description does not match class ' . $reflectionClass->getName());
    }

    public function typeSchemas(ClassSchema $schema, array $namespaces): array
    {
        return $this->factory->typeSchemasFromDescription($schema, $this->describer->describeTypeSchemas($schema, $namespaces));
    }
}
