<?php

namespace Ufo\DTO\Tests\Schema\Enum;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Ufo\DTO\Schema\Enum\EnumDefinition;
use Ufo\DTO\Schema\Enum\EnumResolver;
use Ufo\DTO\Schema\Type\TypeHintResolver;

class EnumDefinitionTest extends TestCase
{
    /**
     * Tests that the method creates an EnumDefinition instance successfully with valid schema.
     */
    public function testFromSchemaCreatesEnumDefinitionSuccessfully(): void
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

        $enumDefinition = EnumDefinition::fromSchema($schema);

        $this->assertInstanceOf(EnumDefinition::class, $enumDefinition);
        $this->assertSame('TestEnum', $enumDefinition->name);
        $this->assertSame(EnumResolver::STRING, $enumDefinition->type);
        $this->assertEquals([
            'VALUE1' => 'value1',
            'VALUE2' => 'value2',
            'VALUE3' => 'value3'
        ], $enumDefinition->values);
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

        EnumDefinition::fromSchema($schema);
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

        $enumDefinition = EnumDefinition::fromSchema($schema, 'parameter');

        $this->assertSame('ParameterEnum', $enumDefinition->name);
        $this->assertEquals([
            'VALUE1' => 'value1',
            'VALUE2' => 'value2'
        ], $enumDefinition->values);
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

        EnumDefinition::fromSchema($schema);
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

        $enumDefinition = EnumDefinition::fromSchema($schema, 'status');

        $this->assertSame('StatusEnum', $enumDefinition->name);
        $this->assertEquals([
            'ONE' => 'one',
            'TWO' => 'two'
        ], $enumDefinition->values);
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

        $enumDefinition = EnumDefinition::fromSchema($schema);

        $this->assertSame(EnumResolver::STRING, $enumDefinition->type);
    }
}