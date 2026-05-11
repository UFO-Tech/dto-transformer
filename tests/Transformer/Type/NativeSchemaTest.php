<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Type;

use ReflectionProperty;
use Ufo\DTO\Attributes\AttrDTO;
use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\Tests\Fixtures\DTO\DTOWithEnumValue;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;
use Ufo\DTO\Tests\Fixtures\DTO\ItemDTO;
use Ufo\DTO\Tests\Fixtures\DTO\UnionWithScalarDTO;
use Ufo\DTO\Tests\Fixtures\DTO\UserDto;
use Ufo\DTO\Tests\Fixtures\DTO\WrapperDTOWithAttr;

final class NativeSchemaTest extends ReflectionTypeSchemaResolverTestCase
{
    public function testNativeSchemaBuildsEnumSchema(): void
    {
        $property = new ReflectionProperty(DTOWithEnumValue::class, 'stringEnum');

        $schema = $this->typeSchemaResolver()->nativeTypeSchema($property->getType());

        $this->assertEnumSchema('StringEnum', ['a', 'b', 'c'], $schema);
    }

    public function testNativeSchemaBuildsNullableUnion(): void
    {
        $property = new ReflectionProperty(UnionWithScalarDTO::class, 'value2');

        $schema = $this->typeSchemaResolver()->nativeTypeSchema($property->getType());
        $oneOf = $schema[TypeHintResolver::ONE_OFF];

        $this->assertCount(3, $oneOf);
        $this->assertObjectSchema(DummyDTO::class, $oneOf[0]);
        $this->assertSame(TypeHintResolver::ARRAY->value, $oneOf[1][TypeHintResolver::TYPE]);
        $this->assertSame(TypeHintResolver::NULL->value, $oneOf[2][TypeHintResolver::TYPE]);
    }

    public function testNativeSchemaBuildsObjectUnion(): void
    {
        $resolvedSchema = $this->resolvedSchemaFor(ItemDTO::class, 'friend');
        $oneOf = $resolvedSchema->schema[TypeHintResolver::ONE_OFF];

        $this->assertFalse($resolvedSchema->fromDocblock);
        $this->assertTrue($resolvedSchema->strict);
        $this->assertObjectSchema(UserDto::class, $oneOf[0]);
        $this->assertObjectSchema(DummyDTO::class, $oneOf[1]);
    }

    public function testSchemaForArrayTypedAttributeFixtureFallsBackToNativeArray(): void
    {
        $resolvedSchema = $this->resolvedSchemaFor($this->arrayAttrWithoutCollectionFixture(), 'value');

        $this->assertFalse($resolvedSchema->fromDocblock);
        $this->assertTrue($resolvedSchema->strict);
        $this->assertSame(TypeHintResolver::ARRAY->value, $resolvedSchema->schema[TypeHintResolver::TYPE]);
    }

    public function testSchemaForAttributeCollectionFixtureUsesNativeArray(): void
    {
        $schema = $this->schemaFor($this->nonStrictCollectionAttrFixture(), 'items');

        $this->assertSame(TypeHintResolver::ARRAY->value, $schema[TypeHintResolver::TYPE]);
    }

    public function testSchemaForAttributeCollectionFixtureUsesNativeObjectOrArrayUnion(): void
    {
        $schema = $this->schemaFor(WrapperDTOWithAttr::class, 'items');
        $oneOf = $schema[TypeHintResolver::ONE_OFF];

        $this->assertCount(2, $oneOf);
        $this->assertObjectSchema(DummyDTO::class, $oneOf[0]);
        $this->assertSame(TypeHintResolver::ARRAY->value, $oneOf[1][TypeHintResolver::TYPE]);
    }

    private function arrayAttrWithoutCollectionFixture(): object
    {
        return new class {
            #[AttrDTO(DummyDTO::class)]
            public array $value;
        };
    }

    private function nonStrictCollectionAttrFixture(): object
    {
        return new class {
            #[AttrDTO(DummyDTO::class, context: [
                AttrDTO::C_COLLECTION => true,
            ])]
            public array $items;
        };
    }
}
