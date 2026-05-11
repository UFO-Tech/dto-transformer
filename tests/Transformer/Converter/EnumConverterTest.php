<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Converter;

use PHPUnit\Framework\TestCase;
use TypeError;
use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Transformer\Converter\EnumConverter;
use Ufo\DTO\Tests\Fixtures\Enum\TestBackedEnum;
use Ufo\DTO\Tests\Fixtures\Enum\TestNonBackedEnum;

final class EnumConverterTest extends TestCase
{
    public function testTransformsBackedEnumValue(): void
    {
        $result = EnumConverter::toEnum(TestBackedEnum::class, 1);

        $this->assertSame(TestBackedEnum::VALUE_1, $result);
    }

    public function testThrowsForInvalidBackedEnumValue(): void
    {
        $this->expectException(BadParamException::class);

        EnumConverter::toEnum(TestBackedEnum::class, 2);
    }

    public function testThrowsForInvalidBackedEnumValueType(): void
    {
        $this->expectException(TypeError::class);

        EnumConverter::toEnum(TestBackedEnum::class, 'sd');
    }

    public function testTransformsNonBackedEnumByName(): void
    {
        $result = EnumConverter::toEnum(TestNonBackedEnum::class, 'CASE_ONE');

        $this->assertSame(TestNonBackedEnum::CASE_ONE, $result);
    }

    public function testThrowsForInvalidNonBackedEnumValue(): void
    {
        $this->expectException(BadParamException::class);

        EnumConverter::toEnum(TestNonBackedEnum::class, 'INVALID_CASE');
    }
}
