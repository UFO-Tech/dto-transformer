<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Schema\Type;

use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Ufo\DTO\Schema\Enum\EnumResolver;
use Ufo\DTO\Schema\Type\ResolvedTypeSchema;
use Ufo\DTO\Schema\Type\TypeHintResolver;
use Ufo\DTO\Schema\Type\TypeSchemaResolverInterface;
use Ufo\DTO\Tests\Support\TransformerFactory;

abstract class ReflectionTypeSchemaResolverTestCase extends TestCase
{
    protected function typeSchemaResolver(): TypeSchemaResolverInterface
    {
        return TransformerFactory::typeSchemaResolver();
    }

    protected function schemaFor(object|string $className, string $propertyName): array
    {
        return $this->typeSchemaResolver()->schema(
            new ReflectionProperty($className, $propertyName),
        )->schema;
    }

    protected function resolvedSchemaFor(object|string $className, string $propertyName): ResolvedTypeSchema
    {
        return $this->typeSchemaResolver()->schema(
            new ReflectionProperty($className, $propertyName),
        );
    }

    protected function assertObjectSchema(string $className, array $schema): void
    {
        $this->assertSame(TypeHintResolver::OBJECT->value, $schema[TypeHintResolver::TYPE]);
        $this->assertSame(true, $schema[TypeHintResolver::ADDITIONAL_PROPERTIES]);
        $this->assertSame($className, $schema[TypeHintResolver::CLASS_FQCN]);
    }

    protected function assertEnumSchema(string $enumName, array $enumValues, array $schema): void
    {
        $this->assertSame($enumName, $schema[EnumResolver::ENUM][EnumResolver::ENUM_NAME]);
        $this->assertSame($enumValues, $schema[EnumResolver::ENUM_KEY]);
    }
}
