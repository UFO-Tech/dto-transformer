<?php

namespace Ufo\DTO\Tests;

use PHPUnit\Framework\TestCase;
use Ufo\DTO\Attributes\AttrDTO;
use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;
use Ufo\DTO\Tests\Fixtures\DTO\MemberDto;
use Ufo\DTO\Tests\Fixtures\DTO\UserDto;

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

}
