<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Hydrator;

use Ufo\DTO\Exceptions\InvalidEnumValueException;
use Ufo\DTO\Helpers\EnumResolver;
use Ufo\DTO\Transformer\Hydrator\EnumParamHydrator;
use Ufo\DTO\Tests\Fixtures\Enum\EmptyIntEnum;
use Ufo\DTO\Tests\Fixtures\Enum\IntEnum;
use Ufo\DTO\Tests\Fixtures\Enum\StringEnum;
use Ufo\DTO\Tests\Fixtures\Enum\TestNonBackedEnum;

final class EnumParamHydratorTest extends ParamHydratorTestCase
{
    public function testSupportsEnumSchema(): void
    {
        $this->assertTrue($this->resolver(EnumParamHydrator::class)->supports(EnumResolver::generateEnumSchema(StringEnum::class)));
    }

    public function testResolvesBackedEnumValueFromSchemaClassFqcn(): void
    {
        $result = $this->resolver(EnumParamHydrator::class)->resolve(
            EnumResolver::generateEnumSchema(StringEnum::class),
            'a',
            $this->context(),
        );

        $this->assertSame(StringEnum::A, $result);
    }

    public function testResolvesBackedEnumValue(): void
    {
        $result = $this->resolver(EnumParamHydrator::class)->resolve(
            EnumResolver::generateEnumSchema(StringEnum::class),
            'a',
            $this->context(['StringEnum' => StringEnum::class]),
        );

        $this->assertSame(StringEnum::A, $result);
    }

    public function testCastsNumericStringForIntBackedEnum(): void
    {
        $result = $this->resolver(EnumParamHydrator::class)->resolve(
            EnumResolver::generateEnumSchema(IntEnum::class),
            '1',
            $this->context(['IntEnum' => IntEnum::class]),
        );

        $this->assertSame(IntEnum::A, $result);
    }

    public function testThrowsForInvalidBackedEnumValue(): void
    {
        $this->expectException(InvalidEnumValueException::class);

        $this->resolver(EnumParamHydrator::class)->resolve(
            EnumResolver::generateEnumSchema(IntEnum::class),
            999,
            $this->context(['IntEnum' => IntEnum::class]),
        );
    }

    public function testHandlesEmptyIntBackedEnum(): void
    {
        $this->expectException(InvalidEnumValueException::class);

        $this->resolver(EnumParamHydrator::class)->resolve(
            EnumResolver::generateEnumSchema(EmptyIntEnum::class),
            1,
            $this->context(['EmptyIntEnum' => EmptyIntEnum::class]),
        );
    }

    public function testTransformsNonBackedEnumByName(): void
    {
        $result = $this->resolver(EnumParamHydrator::class)->resolve(
            EnumResolver::generateEnumSchema(TestNonBackedEnum::class),
            'CASE_ONE',
            $this->context(['TestNonBackedEnum' => TestNonBackedEnum::class]),
        );

        $this->assertSame(TestNonBackedEnum::CASE_ONE, $result);
    }

    public function testThrowsForInvalidNonBackedEnumValue(): void
    {
        $this->expectException(InvalidEnumValueException::class);

        $this->resolver(EnumParamHydrator::class)->resolve(
            EnumResolver::generateEnumSchema(TestNonBackedEnum::class),
            'missing',
            $this->context(['TestNonBackedEnum' => TestNonBackedEnum::class]),
        );
    }

    public function testThrowsForUnsupportedInputType(): void
    {
        $this->expectException(InvalidEnumValueException::class);
        $this->expectExceptionMessage('Enum value must be string or int, array given.');

        $this->resolver(EnumParamHydrator::class)->resolve(
            EnumResolver::generateEnumSchema(StringEnum::class),
            ['a'],
            $this->context(['StringEnum' => StringEnum::class]),
        );
    }

    public function testNormalizesNativeBackedEnumTypeError(): void
    {
        $this->expectException(InvalidEnumValueException::class);
        $this->expectExceptionMessage(sprintf('Invalid value "%s" for enum %s', 'missing', IntEnum::class));

        $this->resolver(EnumParamHydrator::class)->resolve(
            EnumResolver::generateEnumSchema(IntEnum::class),
            'missing',
            $this->context(['IntEnum' => IntEnum::class]),
        );
    }
}
