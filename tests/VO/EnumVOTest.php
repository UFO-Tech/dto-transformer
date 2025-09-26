<?php

namespace Ufo\DTO\Tests\VO;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Ufo\DTO\Helpers\EnumResolver;
use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\VO\EnumVO;

class EnumVOTest extends TestCase
{
    /**
     * Tests that the method creates an EnumVO instance successfully with valid schema.
     */
    public function testFromSchemaCreatesEnumVOSuccessfully(): void
    {
        $schema = [
            EnumResolver::ENUM => [
                EnumResolver::ENUM_NAME => 'TestEnum',
                EnumResolver::METHOD_VALUES => [
                    'VALUE1' => 'value1',
                    'VALUE2' => 'value2',
                    'VALUE3' => 'value3'
                ]
            ],
            TypeHintResolver::TYPE => 'string'
        ];

        $enumVO = EnumVO::fromSchema($schema);

        $this->assertInstanceOf(EnumVO::class, $enumVO);
        $this->assertSame('TestEnum', $enumVO->name);
        $this->assertSame(EnumResolver::STRING, $enumVO->type);
        $this->assertEquals([
            'VALUE1' => 'value1',
            'VALUE2' => 'value2',
            'VALUE3' => 'value3'
        ], $enumVO->values);
    }

    /**
     * Tests that the method throws an exception when the schema is missing required fields.
     */
    public function testFromSchemaThrowsExceptionWhenSchemaIsMissingFields(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Enum schema missing required fields');

        $schema = [
            EnumResolver::ENUM => [
                EnumResolver::ENUM_NAME => 'TestEnum',
            ]
        ];

        EnumVO::fromSchema($schema);
    }

    /**
     * Tests that the method falls back to parameter name for the Enum name if not given in schema.
     */
    public function testFromSchemaUsesParameterNameAsFallbackForEnumName(): void
    {
        $schema = [
            EnumResolver::ENUM => [
                EnumResolver::METHOD_VALUES => [
                    'VALUE1' => 'value1',
                    'VALUE2' => 'value2'
                ]
            ],
            TypeHintResolver::TYPE => 'string'
        ];

        $enumVO = EnumVO::fromSchema($schema, 'parameter');

        $this->assertSame('ParameterEnum', $enumVO->name);
        $this->assertEquals([
            'VALUE1' => 'value1',
            'VALUE2' => 'value2'
        ], $enumVO->values);
    }

    /**
     * Tests that the method throws an exception if values are not an array.
     */
    public function testFromSchemaThrowsExceptionIfValuesNotArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Enum values must be an list of strings or integers');

        $schema = [
            EnumResolver::ENUM => [
                EnumResolver::ENUM_NAME => 'TestEnum',
                EnumResolver::METHOD_VALUES => 'invalidValues'
            ],
            TypeHintResolver::TYPE => 'string'
        ];

        EnumVO::fromSchema($schema);
    }

    /**
     * Tests that ENUM_NAME is derived from paramName even if EnumResolver::ENUM_NAME is absent in the schema.
     */
    public function testFromSchemaHandlesEnumNameFallbackProperly(): void
    {
        $schema = [
            EnumResolver::ENUM => [
                EnumResolver::METHOD_VALUES => [
                    'ONE' => 'one',
                    'TWO' => 'two'
                ]
            ]
        ];

        $enumVO = EnumVO::fromSchema($schema, 'status');

        $this->assertSame('StatusEnum', $enumVO->name);
        $this->assertEquals([
            'ONE' => 'one',
            'TWO' => 'two'
        ], $enumVO->values);
    }

    /**
     * Tests that the method defaults to EnumResolver::STRING when type is invalid or null.
     */
    public function testFromSchemaDefaultsToStringType(): void
    {
        $schema = [
            EnumResolver::ENUM => [
                EnumResolver::ENUM_NAME => 'DefaultTypeEnum',
                EnumResolver::METHOD_VALUES => ['yes', 'no']
            ],
            TypeHintResolver::TYPE => 'invalidType'
        ];

        $enumVO = EnumVO::fromSchema($schema);

        $this->assertSame(EnumResolver::STRING, $enumVO->type);
    }
}