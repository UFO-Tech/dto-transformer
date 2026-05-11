<?php

declare(strict_types=1);

namespace Ufo\DTO\VO;

use ReflectionClass;
use ReflectionProperty;
use Ufo\DTO\Transformer\Converter\DateTimeConverter;

class NormalizationContext
{
    public const string AS_SMART_ARRAY = 'as_smart_array';
    public const string PUBLIC_ONLY = 'public_only';
    public const string RENAME_KEY = 'rename_key';
    public const string PROPERTY = 'property';
    public const string REFLECTION_CLASS = 'reflection_class';
    public const string RUNTIME = 'runtime';
    public const string DEPTH = 'depth';
    public const string MAX_DEPTH = 'max_depth';
    public const string DATE_FORMAT = DateTimeConverter::CONTEXT_OUTPUT_FORMAT;
    public const string DATE_FORMATS = DateTimeConverter::CONTEXT_FORMATS;
    public const string DATE_TIMEZONE = DateTimeConverter::CONTEXT_OUTPUT_TIMEZONE;
    public const string DATE_OUTPUT = DateTimeConverter::CONTEXT_OUTPUT;

    /**
     * @param array<string, string|null> $renameKey
     * @param array<string, mixed> $values
     */
    public function __construct(
        protected array $renameKey = [],
        protected bool $asSmartArray = false,
        protected bool $publicOnly = true,
        protected array $values = [],
        protected ?ReflectionProperty $property = null,
        protected ?ReflectionClass $reflectionClass = null,
        protected int $depth = 0,
    ) {
    }

    /**
     * @param array<string, string|null> $renameKey
     * @param array<string, mixed> $values
     */
    public static function create(
        array $renameKey = [],
        bool $asSmartArray = false,
        bool $publicOnly = true,
        array $values = [],
    ): static
    {
        return new static(
            renameKey: $renameKey,
            asSmartArray: $asSmartArray,
            publicOnly: $publicOnly,
            values: $values,
            depth: (int) ($values[static::DEPTH] ?? 0),
        );
    }


    public function forProperty(ReflectionProperty $property, ReflectionClass $reflectionClass): static
    {
        return new static(
            renameKey: $this->renameKey,
            asSmartArray: $this->asSmartArray,
            publicOnly: $this->publicOnly,
            values: $this->values,
            property: $property,
            reflectionClass: $reflectionClass,
            depth: $this->depth,
        );
    }

    /**
     * @param array<string, mixed> $values
     */
    public function withValues(array $values): static
    {
        return new static(
            renameKey: $this->renameKey,
            asSmartArray: $this->asSmartArray,
            publicOnly: $this->publicOnly,
            values: [...$this->values, ...$values],
            property: $this->property,
            reflectionClass: $this->reflectionClass,
            depth: $this->depth,
        );
    }

    public function nextDepth(): static
    {
        return new static(
            renameKey: $this->renameKey,
            asSmartArray: $this->asSmartArray,
            publicOnly: $this->publicOnly,
            values: $this->values,
            property: $this->property,
            reflectionClass: $this->reflectionClass,
            depth: $this->depth + 1,
        );
    }

    /**
     * @return array<string, string|null>
     */
    public function renameKey(): array
    {
        return $this->renameKey;
    }

    public function asSmartArray(): bool
    {
        return $this->asSmartArray;
    }

    public function publicOnly(): bool
    {
        return $this->publicOnly;
    }

    public function depth(): int
    {
        return $this->depth;
    }


    public function property(): ?ReflectionProperty
    {
        return $this->property;
    }

    public function reflectionClass(): ?ReflectionClass
    {
        return $this->reflectionClass;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return match ($key) {
            static::AS_SMART_ARRAY => $this->asSmartArray,
            static::PUBLIC_ONLY => $this->publicOnly,
            static::RENAME_KEY => $this->renameKey,
            static::PROPERTY => $this->property,
            static::REFLECTION_CLASS => $this->reflectionClass,
            static::DEPTH => $this->depth,
            default => $this->values[$key] ?? $default,
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function values(): array
    {
        return [
            ...$this->values,
            static::AS_SMART_ARRAY => $this->asSmartArray,
            static::PUBLIC_ONLY => $this->publicOnly,
            static::RENAME_KEY => $this->renameKey,
            static::PROPERTY => $this->property,
            static::REFLECTION_CLASS => $this->reflectionClass,
            static::DEPTH => $this->depth,
        ];
    }
}
