<?php

namespace Ufo\DTO\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Ufo\DTO\Helpers\EnumResolver;
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

}