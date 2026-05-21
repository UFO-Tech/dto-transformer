<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Type;

use ReflectionMethod;
use Ufo\DTO\Helpers\EnumResolver;
use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\Tests\Fixtures\DTO\DTOWithEnums;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;

final class EnumSchemaTest extends ReflectionTypeSchemaResolverTestCase
{
    public function testDocblockSchemaBuildsEnum(): void
    {
        $schema = $this->schemaFor($this->fixture(), 'stringEnum');

        $this->assertEnumSchema('StringEnum', ['a', 'b', 'c'], $schema);
    }

    public function testDocblockSchemaBuildsNullableEnum(): void
    {
        $schema = $this->schemaFor($this->fixture(), 'nullableStringEnum');
        $oneOf = $schema[TypeHintResolver::ONE_OFF];

        $this->assertEnumSchema('StringEnum', ['a', 'b', 'c'], $oneOf[0]);
        $this->assertSame(TypeHintResolver::NULL->value, $oneOf[1][TypeHintResolver::TYPE]);
    }

    public function testDocblockSchemaBuildsEnumList(): void
    {
        $schema = $this->schemaFor($this->fixture(), 'stringEnumList');

        $this->assertEnumSchema('StringEnum', ['a', 'b', 'c'], $schema[TypeHintResolver::ITEMS]);
    }

    public function testDocblockSchemaBuildsAssocEnumMap(): void
    {
        $schema = $this->schemaFor($this->fixture(), 'assocStringEnum');

        $this->assertEnumSchema('StringEnum', ['a', 'b', 'c'], $schema[TypeHintResolver::ADDITIONAL_PROPERTIES]);
    }

    public function testDocblockSchemaBuildsAssocEnumOrDtoMap(): void
    {
        $schema = $this->schemaFor($this->fixture(), 'assocEnumOrDummy');
        $valueUnion = $schema[TypeHintResolver::ADDITIONAL_PROPERTIES][TypeHintResolver::ONE_OFF];

        $this->assertEnumSchema('StringEnum', ['a', 'b', 'c'], $valueUnion[0]);
        $this->assertObjectSchema(DummyDTO::class, $valueUnion[1]);
    }

    public function testDocblockSchemaBuildsConstructorEnumUnionCollection(): void
    {
        $constructor = new ReflectionMethod(DTOWithEnums::class, '__construct');

        $resolvedSchema = $this->typeSchemaResolver()->schema($constructor->getParameters()[0]);
        $itemUnion = $resolvedSchema->schema[TypeHintResolver::ITEMS][TypeHintResolver::ONE_OFF];

        $this->assertTrue($resolvedSchema->fromDocblock);
        $this->assertFalse($resolvedSchema->strict);
        $this->assertSame('IntEnum', $itemUnion[0][EnumResolver::ENUM][EnumResolver::ENUM_NAME]);
        $this->assertSame('StringEnum', $itemUnion[1][EnumResolver::ENUM][EnumResolver::ENUM_NAME]);
    }

    private function fixture(): object
    {
        return new class {
            /** @var \Ufo\DTO\Tests\Fixtures\Enum\StringEnum */
            public mixed $stringEnum;

            /** @var \Ufo\DTO\Tests\Fixtures\Enum\StringEnum|null */
            public mixed $nullableStringEnum;

            /** @var array<\Ufo\DTO\Tests\Fixtures\Enum\StringEnum> */
            public array $stringEnumList;

            /** @var array<string, \Ufo\DTO\Tests\Fixtures\Enum\StringEnum> */
            public array $assocStringEnum;

            /** @var array<string, \Ufo\DTO\Tests\Fixtures\Enum\StringEnum|\Ufo\DTO\Tests\Fixtures\DTO\DummyDTO> */
            public array $assocEnumOrDummy;
        };
    }
}
