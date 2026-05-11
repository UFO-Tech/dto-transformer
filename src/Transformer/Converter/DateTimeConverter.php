<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Converter;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Throwable;
use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\Interfaces\Converter\DateTimeValueConverterInterface;
use function array_values;
use function implode;
use function in_array;
use function is_float;
use function is_int;
use function is_numeric;
use function sprintf;

class DateTimeConverter implements DateTimeValueConverterInterface
{
    public const string DEFAULT_FORMAT = 'Y-m-d H:i:s';
    public const string CONTEXT_FORMAT = 'format';
    public const string CONTEXT_FORMATS = 'formats';
    public const string CONTEXT_OUTPUT_FORMAT = 'output_format';
    public const string CONTEXT_TIMEZONE = 'timezone';
    public const string CONTEXT_INPUT_TIMEZONE = 'input_timezone';
    public const string CONTEXT_OUTPUT_TIMEZONE = 'output_timezone';
    public const string CONTEXT_INPUT = 'input';
    public const string CONTEXT_OUTPUT = 'output';
    public const string CONTEXT_TIMESTAMP = 'timestamp';
    public const string MODE_TIMESTAMP = 'timestamp';
    public const string NOW_VALUE = 'now';
    public const string UTC_TIMEZONE = 'UTC';
    public const string TIMESTAMP_FORMAT = 'U';
    public const string TIMESTAMP_MICROSECONDS_FORMAT = 'U.u';
    public const string MICROSECONDS_FORMAT = 'u';
    public const string MICROSECONDS_ZERO = '000000';
    public const string TIMESTAMP_PREFIX = '@';
    public const string WARNING_COUNT_KEY = 'warning_count';
    public const string ERROR_COUNT_KEY = 'error_count';

    protected const array SUPPORTS_MAP = [
        DateTimeImmutable::class,
        DateTime::class,
        DateTimeInterface::class,
    ];

    public function toScalar(object $object, array $context = [], ?callable $callback = null): string|int|float|null
    {
        if (!$object instanceof DateTimeInterface) {
            throw new BadParamException('Object is not a DateTime instance');
        }

        $dateTime = $this->dateTimeForOutput($object, $context);
        $value = $this->isTimestampOutput($context)
            ? $this->timestampValue($dateTime)
            : $dateTime->format($this->outputFormat($context));

        if ($callback !== null) {
            $value = $callback($value, $object);
        }

        return $value;
    }

    public function toObject(int|string|float|null $value, array $context = [], ?callable $callback = null): ?object
    {
        $classFQCN = $this->targetClass($context);

        try {
            $object = $this->isTimestampInput($value, $context)
                ? $this->fromTimestamp($value, $context)
                : $this->fromFormats($value ?? static::NOW_VALUE, $context);
        } catch (Throwable $exception) {
            throw $this->invalidDateException($value, $context, $classFQCN, $exception);
        }

        $object = $this->castToTargetClass($object, $classFQCN);

        if ($callback !== null) {
            $object = $callback($value, $object);
        }

        return $object;
    }

    public function supported(string $classFQCN): bool
    {
        return in_array($classFQCN, static::SUPPORTS_MAP, true);
    }

    protected function targetClass(array $context): string
    {
        $classFQCN = $context[TypeHintResolver::CLASS_FQCN] ?? DateTimeImmutable::class;

        return $classFQCN === DateTimeInterface::class ? DateTimeImmutable::class : $classFQCN;
    }

    protected function formats(array $context): array
    {
        $formats = $context[static::CONTEXT_FORMATS] ?? [];
        if (!is_array($formats)) {
            $formats = [$formats];
        }

        if ($formats === [] && isset($context[static::CONTEXT_FORMAT])) {
            $formats[] = $context[static::CONTEXT_FORMAT];
        }

        if ($formats === []) {
            $formats[] = static::DEFAULT_FORMAT;
        }

        return array_values($formats);
    }

    protected function outputFormat(array $context): string
    {
        return $context[static::CONTEXT_OUTPUT_FORMAT]
            ?? $context[static::CONTEXT_FORMAT]
            ?? $this->formats($context)[0]
            ?? static::DEFAULT_FORMAT;
    }

    protected function inputTimezone(array $context): ?DateTimeZone
    {
        return $this->timezone($context[static::CONTEXT_INPUT_TIMEZONE] ?? $context[static::CONTEXT_TIMEZONE] ?? null);
    }

    protected function outputTimezone(array $context): ?DateTimeZone
    {
        return $this->timezone($context[static::CONTEXT_OUTPUT_TIMEZONE] ?? $context[static::CONTEXT_TIMEZONE] ?? null);
    }

    protected function timezone(DateTimeZone|string|null $timezone): ?DateTimeZone
    {
        if ($timezone === null || $timezone instanceof DateTimeZone) {
            return $timezone;
        }

        try {
            return new DateTimeZone($timezone);
        } catch (Throwable $exception) {
            throw new BadParamException('Invalid timezone "' . $timezone . '"', 0, $exception);
        }
    }

    protected function isTimestampInput(mixed $value, array $context): bool
    {
        return ($context[static::CONTEXT_TIMESTAMP] ?? false) === true
            || ($context[static::CONTEXT_INPUT] ?? null) === static::MODE_TIMESTAMP
            || (is_int($value) || is_float($value));
    }

    protected function isTimestampOutput(array $context): bool
    {
        return ($context[static::CONTEXT_OUTPUT] ?? null) === static::MODE_TIMESTAMP;
    }

    /**
     * @throws \DateMalformedStringException
     */
    protected function fromTimestamp(int|string|float|null $value, array $context): DateTimeImmutable
    {
        $value ??= 0;
        if (!is_numeric($value)) {
            throw new BadParamException('Timestamp value must be numeric');
        }

        $dateTime = str_contains((string) $value, '.')
            ? DateTimeImmutable::createFromFormat(static::TIMESTAMP_MICROSECONDS_FORMAT, sprintf('%.6F', (float) $value), new DateTimeZone(static::UTC_TIMEZONE))
            : new DateTimeImmutable(static::TIMESTAMP_PREFIX . (string) $value);

        if (!$dateTime instanceof DateTimeImmutable) {
            throw new BadParamException('Cannot parse timestamp value');
        }

        $timezone = $this->outputTimezone($context) ?? $this->inputTimezone($context);

        return $timezone ? $dateTime->setTimezone($timezone) : $dateTime;
    }

    protected function fromFormats(string|int|float $value, array $context): DateTimeImmutable
    {
        $timezone = $this->inputTimezone($context);
        foreach ($this->formats($context) as $format) {
            $dateTime = $timezone
                ? DateTimeImmutable::createFromFormat((string) $format, (string) $value, $timezone)
                : DateTimeImmutable::createFromFormat((string) $format, (string) $value);

            if ($dateTime instanceof DateTimeImmutable && $this->lastErrorsAreClean()) {
                return $dateTime;
            }
        }

        if ($this->hasExplicitFormatContext($context)) {
            throw new BadParamException('Normalizer does not match configured date formats');
        }

        return new DateTimeImmutable((string) $value, $timezone);
    }

    protected function hasExplicitFormatContext(array $context): bool
    {
        return isset($context[static::CONTEXT_FORMAT]) || isset($context[static::CONTEXT_FORMATS]);
    }

    protected function lastErrorsAreClean(): bool
    {
        $errors = DateTimeImmutable::getLastErrors();
        if ($errors === false) {
            return true;
        }

        return ($errors[static::WARNING_COUNT_KEY] ?? 0) === 0 && ($errors[static::ERROR_COUNT_KEY] ?? 0) === 0;
    }

    protected function castToTargetClass(DateTimeImmutable $object, string $classFQCN): DateTimeInterface
    {
        if ($classFQCN === DateTime::class) {
            return DateTime::createFromImmutable($object);
        }

        return $object;
    }

    protected function dateTimeForOutput(DateTimeInterface $object, array $context): DateTimeInterface
    {
        $timezone = $this->outputTimezone($context);
        if ($timezone === null) {
            return $object;
        }

        if ($object instanceof DateTimeImmutable) {
            return $object->setTimezone($timezone);
        }

        $dateTime = clone $object;
        $dateTime->setTimezone($timezone);

        return $dateTime;
    }

    protected function timestampValue(DateTimeInterface $dateTime): int|float
    {
        $microseconds = $dateTime->format(static::MICROSECONDS_FORMAT);
        if ($microseconds === static::MICROSECONDS_ZERO) {
            return (int) $dateTime->format(static::TIMESTAMP_FORMAT);
        }

        return (float) $dateTime->format(static::TIMESTAMP_MICROSECONDS_FORMAT);
    }

    protected function invalidDateException(
        int|string|float|null $value,
        array $context,
        string $classFQCN,
        Throwable $exception,
    ): BadParamException
    {
        return new BadParamException(sprintf(
            'Normalizer "%s" is not a valid date for "%s" using formats "%s"',
            (string) $value,
            $classFQCN,
            implode(', ', $this->formats($context)),
        ), 0, $exception);
    }
}
