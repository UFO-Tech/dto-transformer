<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Converter;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Schema\Type\TypeHintResolver;
use Ufo\DTO\Transformer\Converter\DateTimeConverter;

class DateTimeConverterTest extends TestCase
{
    public function testItConvertsDefaultFormatToImmutableObject(): void
    {
        $converter = new DateTimeConverter();

        $dateTime = $converter->toObject('2026-05-08 14:30:00');

        $this->assertInstanceOf(DateTimeImmutable::class, $dateTime);
        $this->assertSame('2026-05-08 14:30:00', $dateTime->format('Y-m-d H:i:s'));
    }

    public function testItSupportsMultipleInputFormats(): void
    {
        $converter = new DateTimeConverter();

        $dateTime = $converter->toObject('2026-05-08', [
            DateTimeConverter::CONTEXT_FORMATS => [
                DateTimeInterface::ATOM,
                'Y-m-d',
            ],
        ]);

        $this->assertSame('2026-05-08', $dateTime->format('Y-m-d'));
    }

    public function testItSupportsAtomAndRfc3339ExtendedFormats(): void
    {
        $converter = new DateTimeConverter();

        $atom = $converter->toObject('2026-05-08T14:30:00+03:00', [
            DateTimeConverter::CONTEXT_FORMATS => [DateTimeInterface::ATOM],
        ]);
        $rfc3339Extended = $converter->toObject('2026-05-08T14:30:00.123+03:00', [
            DateTimeConverter::CONTEXT_FORMATS => [DateTimeInterface::RFC3339_EXTENDED],
        ]);

        $this->assertSame('2026-05-08T14:30:00+03:00', $atom->format(DateTimeInterface::ATOM));
        $this->assertSame('123000', $rfc3339Extended->format('u'));
    }

    public function testItSupportsInputAndOutputTimezones(): void
    {
        $converter = new DateTimeConverter();

        $dateTime = $converter->toObject('2026-05-08 11:00:00', [
            DateTimeConverter::CONTEXT_INPUT_TIMEZONE => 'UTC',
            DateTimeConverter::CONTEXT_OUTPUT_TIMEZONE => 'Europe/Kiev',
        ]);
        $scalar = $converter->toScalar($dateTime, [
            DateTimeConverter::CONTEXT_OUTPUT_TIMEZONE => 'Europe/Kiev',
        ]);

        $this->assertSame('2026-05-08T14:00:00+03:00', $scalar);
    }

    public function testItSupportsTimezoneShorthand(): void
    {
        $converter = new DateTimeConverter();

        $dateTime = $converter->toObject('2026-05-08 14:30:00', [
            DateTimeConverter::CONTEXT_TIMEZONE => new DateTimeZone('Europe/Kiev'),
        ]);

        $this->assertSame('Europe/Kiev', $dateTime->getTimezone()->getName());
    }

    public function testItSupportsTimestampInputAndOutput(): void
    {
        $converter = new DateTimeConverter();

        $dateTime = $converter->toObject(1_746_714_600, [
            DateTimeConverter::CONTEXT_OUTPUT_TIMEZONE => 'UTC',
        ]);
        $timestamp = $converter->toScalar($dateTime, [
            DateTimeConverter::CONTEXT_OUTPUT => DateTimeConverter::MODE_TIMESTAMP,
        ]);

        $this->assertSame(1_746_714_600, $timestamp);
    }

    public function testItSupportsFloatTimestampInput(): void
    {
        $converter = new DateTimeConverter();

        $dateTime = $converter->toObject(1_746_714_600.123456);

        $this->assertSame('123456', $dateTime->format('u'));
    }

    public function testItSupportsMutableTargetClass(): void
    {
        $converter = new DateTimeConverter();

        $dateTime = $converter->toObject('2026-05-08 14:30:00', [
            TypeHintResolver::CLASS_FQCN => DateTime::class,
        ]);

        $this->assertInstanceOf(DateTime::class, $dateTime);
    }

    public function testItSupportsDateTimeInterfaceTargetClass(): void
    {
        $converter = new DateTimeConverter();

        $dateTime = $converter->toObject('2026-05-08 14:30:00', [
            TypeHintResolver::CLASS_FQCN => DateTimeInterface::class,
        ]);

        $this->assertInstanceOf(DateTimeImmutable::class, $dateTime);
    }

    public function testItSupportsCallbackHooks(): void
    {
        $converter = new DateTimeConverter();
        $dateTime = new DateTimeImmutable('2026-05-08 14:30:00', new DateTimeZone('UTC'));

        $value = $converter->toScalar($dateTime, callback: static fn (string $value): string => $value . '.000');

        $this->assertSame('2026-05-08T14:30:00+00:00.000', $value);
    }

    public function testItThrowsOnInvalidDate(): void
    {
        $converter = new DateTimeConverter();

        $this->expectException(BadParamException::class);

        $converter->toObject('not-a-date');
    }

    public function testItThrowsOnInvalidTimezone(): void
    {
        $converter = new DateTimeConverter();

        $this->expectException(BadParamException::class);

        $converter->toObject('2026-05-08 14:30:00', [
            DateTimeConverter::CONTEXT_TIMEZONE => 'Invalid/Timezone',
        ]);
    }

    public function testDefaultOutputKeepsKyivOffsetInSummerAndWinter(): void
    {
        $converter = new DateTimeConverter();
        $kyiv = new DateTimeZone('Europe/Kyiv');

        $this->assertSame('2026-07-01T12:00:00+03:00', $converter->toScalar(new DateTimeImmutable('2026-07-01 12:00:00', $kyiv)));
        $this->assertSame('2026-12-31T12:00:00+02:00', $converter->toScalar(new DateTimeImmutable('2026-12-31 12:00:00', $kyiv)));
    }

    public function testKyivAtomInputKeepsOffsetAndMoment(): void
    {
        $dateTime = new DateTimeConverter()->toObject('2026-07-01T12:00:00+03:00');

        $this->assertSame('+03:00', $dateTime->getTimezone()->getName());
        $this->assertSame(new DateTimeImmutable('2026-07-01T09:00:00Z')->getTimestamp(), $dateTime->getTimestamp());
    }

    public function testKyivDateSurvivesRoundTrip(): void
    {
        $converter = new DateTimeConverter();
        $original = new DateTimeImmutable('2026-12-31 23:30:00', new DateTimeZone('Europe/Kyiv'));

        $restored = $converter->toObject($converter->toScalar($original));

        $this->assertSame($original->getTimestamp(), $restored->getTimestamp());
        $this->assertSame($original->format(DateTimeInterface::ATOM), $restored->format(DateTimeInterface::ATOM));
    }
}

