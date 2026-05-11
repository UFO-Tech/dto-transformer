<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Hydrator;

use Ufo\DTO\Exceptions\BadParamException;
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
        $this->expectException(BadParamException::class);

        $this->resolver(EnumParamHydrator::class)->resolve(
            EnumResolver::generateEnumSchema(IntEnum::class),
            999,
            $this->context(['IntEnum' => IntEnum::class]),
        );
    }

    public function testHandlesEmptyIntBackedEnum(): void
    {
        $this->expectException(BadParamException::class);

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
        $this->expectException(BadParamException::class);

        $this->resolver(EnumParamHydrator::class)->resolve(
            EnumResolver::generateEnumSchema(TestNonBackedEnum::class),
            'missing',
            $this->context(['TestNonBackedEnum' => TestNonBackedEnum::class]),
        );
    }
}
