<?php

namespace Ufo\DTO\Tests;

use PHPUnit\Framework\TestCase;
use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;

class TypeHintResolverTest extends TestCase
{

    public function testNormalizeBasicTypes(): void
    {
        $this->assertSame('', TypeHintResolver::normalize('any'));
        $this->assertSame('', TypeHintResolver::normalize('mixed'));

        $this->assertSame('array', TypeHintResolver::normalize('arr'));
        $this->assertSame('array', TypeHintResolver::normalize('array'));

        $this->assertSame('bool', TypeHintResolver::normalize('bool'));
        $this->assertSame('bool', TypeHintResolver::normalize('true'));
        $this->assertSame('bool', TypeHintResolver::normalize('false'));
        $this->assertSame('bool', TypeHintResolver::normalize('boolean'));

        $this->assertSame('float', TypeHintResolver::normalize('double'));
        $this->assertSame('float', TypeHintResolver::normalize('dbl'));
        $this->assertSame('float', TypeHintResolver::normalize('float'));

        $this->assertSame('int', TypeHintResolver::normalize('int'));
        $this->assertSame('int', TypeHintResolver::normalize('integer'));

        $this->assertSame('null', TypeHintResolver::normalize('null'));
        $this->assertSame('null', TypeHintResolver::normalize('nil'));
        $this->assertSame('null', TypeHintResolver::normalize('void'));

        $this->assertSame('string', TypeHintResolver::normalize('string'));
        $this->assertSame('string', TypeHintResolver::normalize('str'));
    }

    public function testNormalizeUnknownTypeReturnsObject(): void
    {
        $this->assertSame('object', TypeHintResolver::normalize('customType'));
    }
    public function testNormalizeArrayWithMixedTypes(): void
    {
        $input = ['int', 'INTEGER', 'str', 'void', 'unknown', DummyDTO::class];
        $expected = ['int', 'int', 'string', 'null', 'object', 'object'];

        $this->assertSame($expected, TypeHintResolver::normalizeArray($input));
    }

    public function testNormalizeArrayEmpty(): void
    {
        $this->assertSame([], TypeHintResolver::normalizeArray([]));
    }

    public function testIsRealClassWithExistingClass(): void
    {
        $this->assertTrue(TypeHintResolver::isRealClass(\stdClass::class));
    }

    public function testIsRealClassWithBuiltinType(): void
    {
        $this->assertFalse(TypeHintResolver::isRealClass('string'));
        $this->assertFalse(TypeHintResolver::isRealClass('int'));
    }

    public function testIsRealClassWithNonexistentClass(): void
    {
        $this->assertFalse(TypeHintResolver::isRealClass('NonExistent\\ClassName'));
    }

    public function testJsonSchemaToPhpWithScalarTypes(): void
    {
        $this->assertSame('float', TypeHintResolver::jsonSchemaToPhp('number'));
        $this->assertSame('int', TypeHintResolver::jsonSchemaToPhp('integer'));
        $this->assertSame('bool', TypeHintResolver::jsonSchemaToPhp('boolean'));
        $this->assertSame('string', TypeHintResolver::jsonSchemaToPhp('string'));
    }

    public function testJsonSchemaToPhpWithOneOf(): void
    {
        $input = [
            'oneOf' => [
                ['type' => 'number'],
                ['type' => 'boolean'],
                ['type' => 'string'],
            ]
        ];
        $this->assertSame('float|bool|string', TypeHintResolver::jsonSchemaToPhp($input));
    }

    public function testJsonSchemaToPhpWithNestedArray(): void
    {
        $input = ['type' => 'integer'];
        $this->assertSame('int', TypeHintResolver::jsonSchemaToPhp($input));
    }

    public function testJsonSchemaToPhpWithArrayOfString(): void
    {
        $input = ['type' => 'array', 'items' => ['type' => 'string']];
        // метод не обробляє items — очікується просто 'array'
        $this->assertSame('array', TypeHintResolver::jsonSchemaToPhp($input));
    }

    public function testJsonSchemaToPhpWithAllOfIsIgnored(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid schema: missing "type" or "oneOf" key');

        TypeHintResolver::jsonSchemaToPhp(['allOf' => []]);
    }

    public function testPhpToJsonSchemaCastsKnownTypes(): void
    {
        $this->assertSame('', TypeHintResolver::phpToJsonSchema('mixed'));
        $this->assertSame('number', TypeHintResolver::phpToJsonSchema('float'));
        $this->assertSame('integer', TypeHintResolver::phpToJsonSchema('int'));
        $this->assertSame('boolean', TypeHintResolver::phpToJsonSchema('bool'));
    }

    public function testPhpToJsonSchemaPassesThroughUnknownType(): void
    {
        $this->assertSame('string', TypeHintResolver::phpToJsonSchema('string'));
        $this->assertSame('customClass', TypeHintResolver::phpToJsonSchema('customClass'));
    }

    public function testMixedForJsonSchemaReturnsExpectedTypes(): void
    {
        $expected = [
            ['type' => 'string'],
            ['type' => 'integer'],
            ['type' => 'number'],
            ['type' => 'boolean'],
            ['type' => 'array'],
            ['type' => 'null'],
        ];

        $this->assertSame($expected, TypeHintResolver::mixedForJsonSchema());
    }

    public function testToJsonSchemaWithScalarTypes(): void
    {
        $this->assertSame(['type' => 'string'], TypeHintResolver::typeDescriptionToJsonSchema('string'));
        $this->assertSame(['type' => 'integer'], TypeHintResolver::typeDescriptionToJsonSchema('int'));
        $this->assertSame(['type' => 'number'], TypeHintResolver::typeDescriptionToJsonSchema('float'));
        $this->assertSame(['type' => 'boolean'], TypeHintResolver::typeDescriptionToJsonSchema('bool'));
        $this->assertSame(['type' => 'null'], TypeHintResolver::typeDescriptionToJsonSchema('null'));
    }

    public function testToJsonSchemaWithArrayTypes(): void
    {
        $this->assertSame(['type' => 'array', 'items' => ['type' => 'string']],
            TypeHintResolver::typeDescriptionToJsonSchema('string[]'));
        $this->assertSame(['type' => 'array', 'items' => ['type' => 'integer']],
            TypeHintResolver::typeDescriptionToJsonSchema('int[]'));
    }

    public function testToJsonSchemaWithCompoundType(): void
    {
        $this->assertSame(['oneOf' => [['type' => 'string'], ['type' => 'integer']]],
            TypeHintResolver::typeDescriptionToJsonSchema('string|int'));
    }

    public function testToJsonSchemaWithNullableType(): void
    {
        $this->assertSame(
            ['oneOf' => [
                ['type' => 'string'],
                ['type' => 'null'],
            ]],
            TypeHintResolver::typeDescriptionToJsonSchema('?string')
        );
    }

    public function testToJsonSchemaWithObjectType(): void
    {
        $this->assertSame(['type' => 'object', 'additionalProperties' => true],
            TypeHintResolver::typeDescriptionToJsonSchema('\stdClass'));

        $this->assertSame(
            [
                'type' => 'array',
                'items' => ['type' => 'object', 'additionalProperties' => true],
            ],
            TypeHintResolver::typeDescriptionToJsonSchema('\stdClass[]')
        );

        $this->assertSame(
            [
                'type' => 'array',
                'items' => ['type' => 'object', 'additionalProperties' => true],
            ],
            TypeHintResolver::typeDescriptionToJsonSchema('array<\stdClass>')
        );

        $this->assertSame(
            [
                'type' => 'object',
                'additionalProperties' => [
                    'type' => 'object',
                    'additionalProperties' => true
                ]
            ],
            TypeHintResolver::typeDescriptionToJsonSchema('array<string,\stdClass>')
        );

        $this->assertSame(
            [
                'type' => 'array',
                'items' => [
                    'type' => 'array',
                    'items' => ['type' => 'object', 'additionalProperties' => true],
                ],
            ],
            TypeHintResolver::typeDescriptionToJsonSchema('\stdClass[][]')
        );
    }

    public function testToJsonSchemaWithInvalidTypeExpression(): void
    {
        $this->assertSame(['type' => 'any'], TypeHintResolver::typeDescriptionToJsonSchema('!!invalidType!!'));
    }

    public function testToJsonSchemaWithUnknownTypeDefaultsToObject(): void
    {
        $this->assertSame(
            [
                'type' => 'object',
                'additionalProperties' => true,
            ],
            TypeHintResolver::typeDescriptionToJsonSchema('unknownType')
        );
    }
    public function testToJsonSchemaComplexTypes(): void
    {
        $this->assertSame(
            ['oneOf' => [
                ['type' => 'array', 'items' => ['type' => 'string']],
                ['type' => 'array', 'items' => ['type' => 'integer']],
            ]],
            TypeHintResolver::typeDescriptionToJsonSchema('string[]|int[]')
        );

        $this->assertSame(
            ['type' => 'array', 'items' => ['type' => 'string']],
            TypeHintResolver::typeDescriptionToJsonSchema('string[]')
        );

        $this->assertSame(
            ['type' => 'array', 'items' => ['type' => 'string']],
            TypeHintResolver::typeDescriptionToJsonSchema('array<string>')
        );

        $this->assertSame(
            ['oneOf' => [
                ['type' => 'null'],
                ['type' => 'array', 'items' => ['type' => 'string']],
                ['type' => 'array', 'items' => ['type' => 'integer']],
            ]],
            TypeHintResolver::typeDescriptionToJsonSchema('null|string[]|int[]')
        );

        $this->assertSame(
            ['oneOf' => [
                ['type' => 'array', 'items' => ['type' => 'string']],
                ['type' => 'null'],
            ]],
            TypeHintResolver::typeDescriptionToJsonSchema('?string[]')
        );

        $this->assertSame(
            ['type' => 'object', 'additionalProperties' => ['type' => 'string']],
            TypeHintResolver::typeDescriptionToJsonSchema('array<string,string>')
        );

        $this->assertSame(
            ['type' => 'array', 'items' => ['type' => 'string']],
            TypeHintResolver::typeDescriptionToJsonSchema('array<int,string>')
        );

        $this->assertSame(
            ['type' => 'object', 'additionalProperties' => ['type' => 'array', 'items' => ['type' => 'string']]],
            TypeHintResolver::typeDescriptionToJsonSchema('array<string,string[]>')
        );

        $this->assertSame(
            ['type' => 'object', 'additionalProperties' => ['type' => 'object', 'additionalProperties' => ['type' => 'string']]],
            TypeHintResolver::typeDescriptionToJsonSchema('array<string,array<string,string>>')
        );
    }

}
