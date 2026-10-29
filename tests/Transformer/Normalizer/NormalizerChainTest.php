<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Normalizer;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;
use Ufo\DTO\Tests\Fixtures\Enum\OnlyNameEnum;
use Ufo\DTO\Tests\Fixtures\Enum\StringEnum;
use Ufo\DTO\Transformer\Context\NormalizationContext;
use Ufo\DTO\Transformer\Converter\DateTimeConverter;
use Ufo\DTO\Transformer\Normalizer\ArrayNormalizer;
use Ufo\DTO\Transformer\Normalizer\DateTimeNormalizer;
use Ufo\DTO\Transformer\Normalizer\EnumNormalizer;
use Ufo\DTO\Transformer\Normalizer\NormalizerChain;
use Ufo\DTO\Transformer\Normalizer\ScalarNormalizer;

class NormalizerChainTest extends TestCase
{
    public function testItKeepsScalarValue(): void
    {
        $converter = new NormalizerChain([
            new ScalarNormalizer(),
        ]);

        $context = NormalizationContext::create();

        $this->assertTrue($converter->supports('value', $context));
        $this->assertSame('value', $converter->normalize('value', $context));
    }

    public function testItConvertsArrayThroughNormalizerChain(): void
    {
        $converter = new NormalizerChain([
            new ArrayNormalizer(),
            new ScalarNormalizer(),
        ]);

        $value = $converter->normalize(['name' => 'alex'], NormalizationContext::create());

        $this->assertSame(['name' => 'alex'], $value);
    }

    public function testItConvertsBackedEnumToPrimitive(): void
    {
        $converter = new NormalizerChain([
            new EnumNormalizer(),
        ]);

        $context = NormalizationContext::create();

        $this->assertTrue($converter->supports(StringEnum::B, $context));
        $this->assertSame('b', $converter->normalize(StringEnum::B, $context));
    }

    public function testItConvertsUnitEnumToPrimitive(): void
    {
        $converter = new NormalizerChain([
            new EnumNormalizer(),
        ]);

        $context = NormalizationContext::create();

        $this->assertTrue($converter->supports(OnlyNameEnum::A, $context));
        $this->assertSame('A', $converter->normalize(OnlyNameEnum::A, $context));
    }

    public function testItConvertsDateTimeToPrimitive(): void
    {
        $converter = new NormalizerChain([
            new DateTimeNormalizer(new DateTimeConverter()),
        ]);

        $value = $converter->normalize(new DateTimeImmutable('2026-05-08 14:30:00', new DateTimeZone('UTC')), NormalizationContext::create(values: [
            DateTimeConverter::CONTEXT_OUTPUT_FORMAT => DateTimeConverter::DEFAULT_FORMAT,
        ]));

        $this->assertSame('2026-05-08T14:30:00+00:00', $value);
    }

    public function testItThrowsWhenConverterIsMissing(): void
    {
        $converter = new NormalizerChain();

        $this->expectException(BadParamException::class);

        $converter->normalize(new DummyDTO(1, 'Test'), NormalizationContext::create());
    }
}
