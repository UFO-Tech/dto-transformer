<?php

declare(strict_types=1);

namespace Ufo\DTO\VO;

readonly class TransformationContext
{
    public const string C_NAMESPACES = 'namespaces';
    public const string C_STRICT = 'strict';

    /**
     * @param array<string, string>|string[] $namespaces
     * @param array<string, mixed> $context
     */
    public function __construct(
        public array $namespaces = [],
        public bool $strict = false,
        private array $context = [],
    ) {}

    /**
     * @param array<string, mixed> $context
     * @param array<string, string>|string[] $namespaces
     */
    public static function fromArray(
        array $context = [],
        array $namespaces = [],
        bool $strict = false,
    ): self
    {
        $namespaces = [
            ...$namespaces,
            ...($context[static::C_NAMESPACES] ?? []),
        ];
        $strict = (bool) ($context[static::C_STRICT] ?? $strict);
        unset(
            $context[static::C_NAMESPACES],
            $context[static::C_STRICT],
        );

        return new self(
            namespaces: $namespaces,
            strict: $strict,
            context: $context,
        );
    }

    public function getFromContext(string $key, mixed $default = null): mixed
    {
        return $this->context[$key] ?? $default;
    }

    /**
     * @return array<string, string>|string[]
     */
    public function namespaces(): array
    {
        return $this->namespaces;
    }

    public function isStrict(): bool
    {
        return $this->strict;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            ...$this->context,
            static::C_NAMESPACES => $this->namespaces,
            static::C_STRICT => $this->strict,
        ];
    }

    /**
     * @param array<string, string>|string[] $namespaces
     */
    public function withNamespaces(array $namespaces): self
    {
        return new self(
            namespaces: [...$this->namespaces, ...$namespaces],
            strict: $this->strict,
            context: $this->context,
        );
    }

    public function withStrict(bool $strict): self
    {
        return new self(
            namespaces: $this->namespaces,
            strict: $strict,
            context: $this->context,
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    public function withContext(array $context): self
    {
        return static::fromArray(
            context: $context,
            namespaces: $this->namespaces,
            strict: $this->strict,
        );
    }
}
