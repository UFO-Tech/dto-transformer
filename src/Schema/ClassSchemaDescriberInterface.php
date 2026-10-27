<?php

declare(strict_types=1);

namespace Ufo\DTO\Schema;

use ReflectionClass;
use ReflectionException;

/**
 * Серіалізовні описи схеми класу та схем типів його властивостей.
 */
interface ClassSchemaDescriberInterface
{
    /**
     * @return array<string, mixed>
     * @throws ReflectionException
     */
    public function describe(ReflectionClass $reflectionClass): array;

    /**
     * Опис наново, коли наявний не відповідає класу.
     *
     * @return array<string, mixed>
     * @throws ReflectionException
     */
    public function redescribe(ReflectionClass $reflectionClass): array;

    /**
     * @param array<string, string> $namespaces
     * @return array<string, array{array, bool, bool}|null> за іменем властивості
     */
    public function describeTypeSchemas(ClassSchema $schema, array $namespaces): array;
}
