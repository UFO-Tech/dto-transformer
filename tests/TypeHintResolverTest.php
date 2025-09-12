<?php

namespace Ufo\DTO\Tests;


use PHPUnit\Framework\TestCase;
use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\Tests\Fixtures\DTO\AliasDTO;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;
use Ufo\DTO\Tests\Fixtures\DTO\MemberWithFriendsDTO;

class TypeHintResolverTest extends TestCase
{
    public function testTypeToSchemaWithCompoundType(): void
    {
        $expected = [
            'oneOf' => [
                ['type' => 'string'],
                ['type' => 'integer'],
                ['type' => 'number'],
                ['type' => 'boolean'],
                ['type' => 'array'],
                ['type' => 'null'],
            ],
        ];
        $this->assertSame($expected, TypeHintResolver::typeDescriptionToJsonSchema('mixed'));
    }

    public function testGetUsesNamespacesWithValidClass(): void
    {
        $uses = TypeHintResolver::getUsesNamespaces(MemberWithFriendsDTO::class);
        $this->assertIsArray($uses);
        $this->assertArrayHasKey('DummyDTO', $uses); // Example namespace alias
    }

    public function testGetUsesNamespacesWithInvalidClass(): void
    {
        $uses = TypeHintResolver::getUsesNamespaces('NonExistent\\ClassName');
        $this->assertIsArray($uses);
        $this->assertEmpty($uses);
    }

    public function testGetUsesNamespacesWithoutNamespaceAliases(): void
    {
        $uses = TypeHintResolver::getUsesNamespaces(\stdClass::class);
        $this->assertIsArray($uses);
        $this->assertEmpty($uses);
    }

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
        $this->assertSame(
            ['type' => 'object', 'additionalProperties' => true, 'classFQCN' => 'My\StdClass'],
            TypeHintResolver::typeDescriptionToJsonSchema('\stdClass', ['stdClass' => 'My\StdClass'])
        );

        $this->assertSame(
            ['type' => 'object', 'additionalProperties' => true, 'classFQCN' => DummyDTO::class],
            TypeHintResolver::typeDescriptionToJsonSchema('\DummyDTO', ['DummyDTO' => DummyDTO::class])
        );

        $this->assertSame(
            [
                'type' => 'array',
                'items' => ['type' => 'object', 'additionalProperties' => true, 'classFQCN' => DummyDTO::class],
            ],
            TypeHintResolver::typeDescriptionToJsonSchema('\DummyDTO[]', ['DummyDTO' => DummyDTO::class])
        );

        $this->assertSame(
            [
                'type' => 'array',
                'items' => ['type' => 'object', 'additionalProperties' => true],
            ],
            TypeHintResolver::typeDescriptionToJsonSchema('\DummyDTO[]')
        );

        $this->assertSame(
            [
                'type' => 'array',
                'items' => ['type' => 'object', 'additionalProperties' => true],
            ],
            TypeHintResolver::typeDescriptionToJsonSchema('array<\DummyDTO>')
        );

        $this->assertSame(
            [
                'type' => 'object',
                'additionalProperties' => [
                    'type' => 'object',
                    'additionalProperties' => true
                ]
            ],
            TypeHintResolver::typeDescriptionToJsonSchema('array<string,\DummyDTO>')
        );

        $this->assertSame(
            [
                'type' => 'array',
                'items' => [
                    'type' => 'array',
                    'items' => ['type' => 'object', 'additionalProperties' => true],
                ],
            ],
            TypeHintResolver::typeDescriptionToJsonSchema('\DummyDTO[][]')
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

        $this->assertSame(
            [
                'oneOf' => [
                    ['type' => 'object', 'additionalProperties' => true, 'classFQCN' => DummyDTO::class],
                    ['type' => 'object', 'additionalProperties' => true, 'classFQCN' => AliasDTO::class],
                ]
            ],
            TypeHintResolver::typeDescriptionToJsonSchema(DummyDTO::class . '|' . AliasDTO::class)
        );
    }

    public function testJsonSchemaToPhpDescription(): void
    {
        $this->assertSame('string', TypeHintResolver::jsonSchemaToTypeDescription(['type' => 'string']));
        $this->assertSame('int', TypeHintResolver::jsonSchemaToTypeDescription(['type' => 'integer']));
        $this->assertSame('float', TypeHintResolver::jsonSchemaToTypeDescription(['type' => 'number']));
        $this->assertSame('bool', TypeHintResolver::jsonSchemaToTypeDescription(['type' => 'boolean']));
        $this->assertSame('null', TypeHintResolver::jsonSchemaToTypeDescription(['type' => 'null']));
        $this->assertSame('string[]',
            TypeHintResolver::jsonSchemaToTypeDescription(['type' => 'array', 'items' => ['type' => 'string']]));
        $this->assertSame('int[]',
            TypeHintResolver::jsonSchemaToTypeDescription(['type' => 'array', 'items' => ['type' => 'integer']]));
        $this->assertSame('string|int',
            TypeHintResolver::jsonSchemaToTypeDescription(['oneOf' => [['type' => 'string'], ['type' => 'integer']]]));
        $this->assertSame('?string', TypeHintResolver::jsonSchemaToTypeDescription([
                'oneOf' => [
                    ['type' => 'string'],
                    ['type' => 'null'],
                ],
            ]));
        $this->assertSame('\stdClass', TypeHintResolver::jsonSchemaToTypeDescription([
            'type'                 => 'object',
            'additionalProperties' => true,
            'classFQCN'            => '\stdClass',
        ]));
        $this->assertSame('array', TypeHintResolver::jsonSchemaToTypeDescription([
            'type'                 => 'object',
            'additionalProperties' => true,
        ]));
        $this->assertSame(DummyDTO::class, TypeHintResolver::jsonSchemaToTypeDescription([
            'type'                 => 'object',
            'additionalProperties' => true,
            'classFQCN'            => DummyDTO::class,
        ]));
        $this->assertSame(DummyDTO::class . '[]', TypeHintResolver::jsonSchemaToTypeDescription([
            'type'  => 'array',
            'items' => ['type' => 'object', 'additionalProperties' => true, 'classFQCN' => DummyDTO::class],
        ]));
        $this->assertSame('array[]', TypeHintResolver::jsonSchemaToTypeDescription([
            'type'  => 'array',
            'items' => ['type' => 'object', 'additionalProperties' => true],
        ]));
        $this->assertSame('array<string,array<string,string>>', TypeHintResolver::jsonSchemaToTypeDescription(
            [
                'type' => 'object',
                'additionalProperties' => [
                    'type' => 'object',
                    'additionalProperties' => [
                        'type' => 'string'
                    ]
                ]
            ]
        ));
        $this->assertSame('array[][]', TypeHintResolver::jsonSchemaToTypeDescription([
            'type'  => 'array',
            'items' => [
                'type'  => 'array',
                'items' => ['type' => 'object', 'additionalProperties' => true],
            ],
        ]));
        $this->assertSame('mixed', TypeHintResolver::jsonSchemaToTypeDescription(['oneOf' => [
            ['type' => 'string'],
            ['type' => 'integer'],
            ['type' => 'number'],
            ['type' => 'boolean'],
            ['type' => 'array'],
            ['type' => 'null'],
        ]]));

        $this->assertSame('array', TypeHintResolver::jsonSchemaToTypeDescription([
            'type'                 => 'object',
            'additionalProperties' => true,
        ]));
        $this->assertSame('string[]|int[]', TypeHintResolver::jsonSchemaToTypeDescription([
            'oneOf' => [
                ['type' => 'array', 'items' => ['type' => 'string']],
                ['type' => 'array', 'items' => ['type' => 'integer']],
            ],
        ]));
        $this->assertSame('string[]',
            TypeHintResolver::jsonSchemaToTypeDescription(['type' => 'array', 'items' => ['type' => 'string']]));
        $this->assertSame('null|string[]|int[]', TypeHintResolver::jsonSchemaToTypeDescription([
            'oneOf' => [
                ['type' => 'null'],
                ['type' => 'array', 'items' => ['type' => 'string']],
                ['type' => 'array', 'items' => ['type' => 'integer']],
            ],
        ]));
        $this->assertSame('?string[]', TypeHintResolver::jsonSchemaToTypeDescription([
            'oneOf' => [
                ['type' => 'array', 'items' => ['type' => 'string']],
                ['type' => 'null'],
            ],
        ]));
        $this->assertSame('array<string,string>', TypeHintResolver::jsonSchemaToTypeDescription([
            'type'                 => 'object',
            'additionalProperties' => ['type' => 'string'],
        ]));
        $this->assertSame('string[]',
            TypeHintResolver::jsonSchemaToTypeDescription(['type' => 'array', 'items' => ['type' => 'string']]));
        $this->assertSame('array<string,string[]>', TypeHintResolver::jsonSchemaToTypeDescription([
            'type'                 => 'object',
            'additionalProperties' => ['type' => 'array', 'items' => ['type' => 'string']],
        ]));
        $this->assertSame('array<string,array<string,string>>', TypeHintResolver::jsonSchemaToTypeDescription([
            'type'                 => 'object',
            'additionalProperties' => ['type' => 'object', 'additionalProperties' => ['type' => 'string']],
        ]));
    }

    public function testJsonSchemaToPhpType(): void
    {
        $schema = [
            'type'                 => 'object',
            'additionalProperties' => ['type' => 'object', 'additionalProperties' => ['type' => 'string']],
        ];

        $simpleSchema = [
            'type'                 => 'object',
            'additionalProperties' => ['type' => 'string'],
        ];

        $onOffSchema = [
            'oneOf' => [
                [
                    'type'                 => 'object',
                    'additionalProperties' => ['type' => 'string'],
                ],
                [
                    'type'                 => 'null',
                ]
            ]
        ];

        $resultCollection = TypeHintResolver::jsonSchemaToPhp($schema);
        $resultArray = TypeHintResolver::jsonSchemaToPhp($simpleSchema);
        $resultOnOff = TypeHintResolver::jsonSchemaToPhp($onOffSchema);;

        $this->assertSame('array', $resultCollection);
        $this->assertSame('array', $resultArray);
        $this->assertContains($resultOnOff, ['array|null', '?array']);
    }

    public function testObjectJsonSchema(): void
    {
        $array = json_decode('{"default": null, "oneOf": [{ "$ref": "#/components/schemas/CreateInvoiceDetailDTO"},{"type": "null"}]}', true);

        $result = TypeHintResolver::jsonSchemaToPhp($array);
        $resultWithNamespace = TypeHintResolver::jsonSchemaToPhp($array, 'DTO');

        $resultObject = TypeHintResolver::jsonSchemaToPhp(['$ref' => '#/Qqq']);
        $resultObjectWithNamespace = TypeHintResolver::jsonSchemaToPhp(['$ref' => '#/Qqq'], 'DTO');

        $this->assertContains($result, ['\CreateInvoiceDetailDTO|null', '?\CreateInvoiceDetailDTO']);
        $this->assertContains($resultWithNamespace, ['DTO\CreateInvoiceDetailDTO|null', '?DTO\CreateInvoiceDetailDTO']);
        $this->assertEquals('\Qqq', $resultObject);
        $this->assertEquals('DTO\Qqq', $resultObjectWithNamespace);
    }
}
