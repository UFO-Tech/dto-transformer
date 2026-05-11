<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Normalizer;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Transformer\Converter\DateTimeConverter;
use Ufo\DTO\Transformer\Normalizer\ArrayNormalizer;
use Ufo\DTO\Transformer\Normalizer\DateTimeNormalizer;
use Ufo\DTO\Transformer\Normalizer\EnumNormalizer;
use Ufo\DTO\Transformer\Normalizer\PropertyNormalizer;
use Ufo\DTO\Transformer\Normalizer\ScalarValueConverter;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;
use Ufo\DTO\Tests\Fixtures\Enum\OnlyNameEnum;
use Ufo\DTO\Tests\Fixtures\Enum\StringEnum;
use Ufo\DTO\VO\NormalizationContext;

class PropertyNormalizerTest extends TestCase
{
    public function testItKeepsScalarValue(): void
    {
        $converter = new PropertyNormalizer([
            new ScalarValueConverter(),
        ]);

        $context = NormalizationContext::create();

        $this->assertTrue($converter->supports('value', $context));
        $this->assertSame('value', $converter->normalize('value', $context));
    }

    public function testItConvertsArrayThroughPropertyNormalizerChain(): void
    {
        $converter = new PropertyNormalizer([
            new ArrayNormalizer(),
            new ScalarValueConverter(),
        ]);

        $value = $converter->normalize(['name' => 'alex'], NormalizationContext::create());

        $this->assertSame(['name' => 'alex'], $value);
    }

    public function testItConvertsBackedEnumToPrimitive(): void
    {
        $converter = new PropertyNormalizer([
            new EnumNormalizer(),
        ]);

        $context = NormalizationContext::create();

        $this->assertTrue($converter->supports(StringEnum::B, $context));
        $this->assertSame('b', $converter->normalize(StringEnum::B, $context));
    }

    public function testItConvertsUnitEnumToPrimitive(): void
    {
        $converter = new PropertyNormalizer([
            new EnumNormalizer(),
        ]);

        $context = NormalizationContext::create();

        $this->assertTrue($converter->supports(OnlyNameEnum::A, $context));
        $this->assertSame('A', $converter->normalize(OnlyNameEnum::A, $context));
    }

    public function testItConvertsDateTimeToPrimitive(): void
    {
        $converter = new PropertyNormalizer([
            new DateTimeNormalizer(),
        ]);

        $value = $converter->normalize(new DateTimeImmutable('2026-05-08 14:30:00'), NormalizationContext::create(values: [
            DateTimeConverter::CONTEXT_OUTPUT_FORMAT => DateTimeConverter::DEFAULT_FORMAT,
        ]));

        $this->assertSame('2026-05-08 14:30:00', $value);
    }

    public function testItThrowsWhenConverterIsMissing(): void
    {
        $converter = new PropertyNormalizer();

        $this->expectException(BadParamException::class);

        $converter->normalize(new DummyDTO(1, 'Test'), NormalizationContext::create());
    }
}
