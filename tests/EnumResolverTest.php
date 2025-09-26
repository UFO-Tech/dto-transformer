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

    /**
     * Test `applyEnumFqcnToJsonSchema` when merging enum schema into a valid JSON schema.
     */
    public function testApplyEnumFqcnToJsonSchemaMergesEnumSchema(): void
    {
        $enumFQCN = StringEnum::class;
        $jsonSchema = [
            'type' => 'object',
            'properties' => [
                'example' => ['type' => 'string']
            ]
        ];
        $expectedSchema = [
            'type' => 'object',
            'properties' => [
                'example' => [
                    'type' => 'string',
                    'x-ufo-enum' => [
                        'name' => 'StringEnum',
                        'values' => [
                            'A' => 'a',
                            'B' => 'b',
                            'C' => 'c',
                        ],
                    ],
                    'enum' => ['a', 'b', 'c'],
                ]
            ]
        ];

        $result = EnumResolver::applyEnumFqcnToJsonSchema($enumFQCN, $jsonSchema['properties']['example']);
        $jsonSchema['properties']['example'] = $result;

        $this->assertSame($expectedSchema, $jsonSchema);
    }

    /**
     * Test `applyEnumFqcnToJsonSchema` when handling array schema with enum.
     */
    public function testApplyEnumFqcnToJsonSchemaWithArraySchema(): void
    {
        $enumFQCN = IntEnum::class;
        $jsonSchema = [
            'type' => 'array',
            'items' => ['type' => 'integer']
        ];
        $expectedSchema = [
            'type' => 'array',
            'items' => [
                'type' => 'integer',
                'x-ufo-enum' => [
                    'name' => 'IntEnum',
                    'values' => [
                        'A' => 1,
                        'B' => 2,
                        'C' => 3,
                    ],
                ],
                'enum' => [1, 2, 3]
            ]
        ];

        $result = EnumResolver::applyEnumFqcnToJsonSchema($enumFQCN, $jsonSchema);
        $this->assertSame($expectedSchema, $result);
    }

    /**
     * Test `applyEnumFqcnToJsonSchema` when handling oneOf schema.
     */
    public function testApplyEnumFqcnToJsonSchemaWithOneOfSchema(): void
    {
        $enumFQCN = StringEnum::class;
        $jsonSchema = [
            'oneOf' => [
                ['type' => 'string'],
                ['type' => 'null']
            ]
        ];
        $expectedSchema = [
            'oneOf' => [
                [
                    'type' => 'string',
                    'x-ufo-enum' => [
                        'name' => 'StringEnum',
                        'values' => [
                            'A' => 'a',
                            'B' => 'b',
                            'C' => 'c',
                        ],
                    ],
                    'enum' => ['a', 'b', 'c'],
                ],
                ['type' => 'null']
            ]
        ];

        $result = EnumResolver::applyEnumFqcnToJsonSchema($enumFQCN, $jsonSchema);
        $this->assertSame($expectedSchema, $result);
    }

    /**
     * Test `applyEnumFqcnToJsonSchema` when invalid type is provided.
     */
    public function testApplyEnumFqcnToJsonSchemaWithInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $enumFQCN = IntEnum::class;
        $jsonSchema = ['type' => 'invalid'];
        EnumResolver::applyEnumFqcnToJsonSchema($enumFQCN, $jsonSchema, methodTryFrom: 'tryFromValue2');
    }

    /**
     * Test that `getEnumFQCN` returns a valid FQCN for an enum.
     */
    public function testGetEnumFQCNReturnsValidFQCN(): void
    {
        $result = EnumResolver::getEnumFQCN(StringEnum::class);
        $this->assertSame(StringEnum::class, $result);
    }

    /**
     * Test that `getEnumFQCN` returns null for a non-enum string.
     */
    public function testGetEnumFQCNReturnsNullForNonEnumString(): void
    {
        $result = EnumResolver::getEnumFQCN('NonEnumClass');
        $this->assertNull($result);
    }

    /**
     * Test that `getEnumFQCN` returns null for invalid or empty types.
     */
    public function testGetEnumFQCNReturnsNullForInvalidType(): void
    {
        $result = EnumResolver::getEnumFQCN('');
        $this->assertNull($result);

        $result = EnumResolver::getEnumFQCN('null');
        $this->assertNull($result);
    }

    /**
     * Test `getEnumFQCN` returns the correct enum FQCN from nested arrays.
     */
    public function testGetEnumFQCNReturnsEnumFQCNForNestedArray(): void
    {
        $type = ['null', 'NonEnumClass', StringEnum::class];
        $result = EnumResolver::getEnumFQCN($type);
        $this->assertSame(StringEnum::class, $result);
    }

    public function testEnumArrays(): void
    {
        $type = json_decode(
            '{"type":"array","minLength":1,"items":{"oneOf":[{"type":"string","enum":["a","b","c"]},{"type":"null"}]}}',
            true
        );

        $result = EnumResolver::enumDataFromSchema($type, 'String');

        $this->assertSame('StringEnum', $result->name);
        $this->assertSame([
            'A' => 'a',
                'B' => 'b',
                'C' => 'c',
            ], $result->values
        );
        $this->assertSame(TypeHintResolver::STRING->value, $result->type->value);
    }

    /**
     * Test `enumsDataFromSchema` extracts multiple EnumVO objects from a valid schema.
     */
    public function testEnumsDataFromSchemaWithValidSchema(): void
    {
        $schema = [
            'oneOf' => [
                [
                    'type' => 'string',
                    'x-ufo-enum' => [
                        'name' => 'StringEnum',
                        'values' => [
                            'A' => 'a',
                            'B' => 'b',
                            'C' => 'c',
                        ],
                    ],
                    'enum' => ['a', 'b', 'c'],
                ],
                [
                    'type' => 'integer',
                    'x-ufo-enum' => [
                        'name' => 'IntEnum',
                        'values' => [
                            'ONE' => 1,
                            'TWO' => 2,
                            'THREE' => 3,
                        ],
                    ],
                    'enum' => [1, 2, 3],
                ],
            ],
        ];

        $result = EnumResolver::enumsDataFromSchema($schema);
        $this->assertCount(2, $result);

        $this->assertSame('StringEnum', $result[0]->name);
        $this->assertSame(['A' => 'a', 'B' => 'b', 'C' => 'c'], $result[0]->values);

        $this->assertSame('IntEnum', $result[1]->name);
        $this->assertSame(['ONE' => 1, 'TWO' => 2, 'THREE' => 3], $result[1]->values);
    }

    /**
     * Test `enumsDataFromSchema` returns an empty array when there are no enums.
     */
    public function testEnumsDataFromSchemaWithoutEnums(): void
    {
        $schema = [
            'type' => 'object',
            'properties' => ['example' => ['type' => 'string']],
        ];

        $result = EnumResolver::enumsDataFromSchema($schema);
        $this->assertEmpty($result);
    }

    /**
     * Test `enumsDataFromSchema` extracts only valid enums from a mixed schema.
     */
    public function testEnumsDataFromSchemaWithPartialEnums(): void
    {
        $schema = [
            'oneOf' => [
                [
                    'type' => 'string',
                    'x-ufo-enum' => [
                        'name' => 'PartialEnum',
                        'values' => [
                            'A' => 'alpha',
                            'B' => 'beta',
                        ],
                    ],
                    'enum' => ['alpha', 'beta'],
                ],
                [
                    'type' => 'null',
                ],
                [
                    'type' => 'integer',
                ],
            ],
        ];

        $result = EnumResolver::enumsDataFromSchema($schema);
        $this->assertCount(1, $result);

        $this->assertSame('PartialEnum', $result[0]->name);
        $this->assertSame(['A' => 'alpha', 'B' => 'beta'], $result[0]->values);
    }
}