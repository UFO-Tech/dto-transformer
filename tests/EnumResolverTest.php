<?php

namespace Ufo\DTO\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Ufo\DTO\Helpers\EnumResolver;
use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\Tests\Fixtures\Enum\IntEnum;
use Ufo\DTO\Tests\Fixtures\Enum\StringEnum;

class EnumResolverTest extends TestCase
{
    /**
     * Test that `generateEnumSchema` correctly generates the schema for a valid enum.
     *
     * @throws ReflectionException
     */
    public function testGenerateEnumSchemaValidEnum(): void
    {
        $expectedSchema = [
            'type'       => 'string',
            'x-ufo-enum' => [
                'name'   => 'StringEnum',
                'values' => [
                    'A' => 'a',
                    'B' => 'b',
                    'C' => 'c',
                ],
            ],
            'enum'       => ['a', 'b', 'c'],
        ];
        $result = EnumResolver::generateEnumSchema(StringEnum::class, EnumResolver::METHOD_VALUES);
        $this->assertSame($expectedSchema, $result);
    }

    /**
     * Test that `generateEnumSchema` throws an exception for an invalid enum class name.
     */
    public function testGenerateEnumSchemaInvalidEnumClass(): void
    {
        $this->expectException(ReflectionException::class);
        EnumResolver::generateEnumSchema('NonExistentEnum');
    }

    /**
     * Test that `generateEnumSchema` throws an exception when the method is missing.
     */
    public function testGenerateEnumSchemaMissingMethod(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value "1" for enum Ufo\DTO\Tests\Fixtures\Enum\IntEnum');
        EnumResolver::generateEnumSchema(IntEnum::class, methodTryFrom: 'tryFromValueError');
    }

    /**
     * Test `generateEnumSchema` when the enum has no extra methods but cases are valid.
     *
     * @throws ReflectionException
     */
    public function testGenerateEnumSchemaWithoutAdditionalMethods(): void
    {

        $expectedSchema = [
            'type'       => 'integer',
            'x-ufo-enum' => [
                'name'   => 'IntEnum',
                'values' => [
                    'A' => 1,
                    'B' => 2,
                    'C' => 3,
                ],
            ],
            'enum'       => [1,2,3],
        ];

        $result = EnumResolver::generateEnumSchema(IntEnum::class);
        $this->assertSame($expectedSchema, $result);
    }

    public function testEnumDataFromSchemaWithValidSchema(): void
    {
        $schema = [
            'type'       => 'string',
            'x-ufo-enum' => [
                'name'   => 'StringEnum',
                'values' => [
                    'A' => 'a',
                    'B' => 'b',
                    'C' => 'c',
                ],
            ],
            'enum'       => ['a', 'b', 'c'],
        ];

        $result = EnumResolver::enumDataFromSchema($schema, 'StringEnum');
        $this->assertSame('StringEnum', $result->name);
        $this->assertSame([
            'A' => 'a',
            'B' => 'b',
            'C' => 'c',
        ], $result->values);
    }

    public function testEnumDataFromSchemaWithoutEnumInSchema(): void
    {
        $schema = ['type' => 'string'];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Enum schema missing required fields');
        EnumResolver::enumDataFromSchema($schema, 'tmp');

    }

    public function testEnumDataFromSchemaWithoutEnumInSchema2(): void
    {
        $schema = ['type' => 'string', 'enum' => ['a', 'b', 'c']];;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Enum name is required');
        EnumResolver::enumDataFromSchema($schema);
    }

    public function testEnumDataFromSchemaWithOneOfSchema(): void
    {
        $schema = [
            'oneOf' => [
                [
                    'type'       => 'integer',
                    'x-ufo-enum' => [
                        'name'   => 'IntEnum',
                        'values' => [
                            'ONE'   => 1,
                            'TWO'   => 2,
                            'THREE' => 3,
                        ],
                    ],
                    'enum'       => [1, 2, 3],
                ],
                ['type' => 'null'],
            ],
        ];

        $result = EnumResolver::enumDataFromSchema($schema, 'IntEnum');
        $this->assertSame('IntEnum', $result->name);
        $this->assertSame([
            'ONE'   => 1,
            'TWO'   => 2,
            'THREE' => 3,
        ], $result->values);
    }

    public function testEnumDataFromSchemaWithIncorrectSchema(): void
    {
        $schema = ['enum' => 'value'];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Enum values must be an list of strings or integers');

        EnumResolver::enumDataFromSchema($schema, 'test');
    }

    public function testSchemaHasEnumInOneOf(): void
    {
        $schema = [
            TypeHintResolver::ONE_OFF => [
                [EnumResolver::ENUM => ['name' => 'TestEnum']],
            ]
        ];

        self::assertTrue(EnumResolver::schemaHasEnum($schema));
    }

    public function testSchemaHasEnumInItems(): void
    {
        $schema = [
            TypeHintResolver::ITEMS => [
                EnumResolver::ENUM_KEY => ['A', 'B'],
            ]
        ];

        self::assertTrue(EnumResolver::schemaHasEnum($schema));
    }

    public function testSchemaHasEnumAtRoot(): void
    {
        $schema = [
            EnumResolver::ENUM => ['name' => 'RootEnum']
        ];

        self::assertTrue(EnumResolver::schemaHasEnum($schema));
    }

    public function testSchemaHasEnumReturnsFalse(): void
    {
        $schema = [
            TypeHintResolver::ITEMS => [
                'type' => 'string',
            ]
        ];

        self::assertFalse(EnumResolver::schemaHasEnum($schema));
    }
}