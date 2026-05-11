<?php

declare(strict_types=1);

namespace Ufo\DTO\VO;

readonly class TransformationContext
{
    /**
     * @param array<string, string>|string[] $namespaces
     * @param array<string, string>|string[] $classes
     */
    public function __construct(
        public array $namespaces = [],
        public array $classes = [],
        public bool $strict = false,
    ) {}

    /**
     * @param array<string, string>|string[] $namespaces
     */
    public function withNamespaces(array $namespaces): self
    {
        return new self(
            namespaces: [...$this->namespaces, ...$namespaces],
            classes: $this->classes,
            strict: $this->strict,
        );
    }

    public function withStrict(bool $strict): self
    {
        return new self(
            namespaces: $this->namespaces,
            classes: $this->classes,
            strict: $strict,
        );
    }
}
