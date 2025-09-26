<?php

namespace Ufo\DTO\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD|Attribute::TARGET_PROPERTY|Attribute::TARGET_PARAMETER)]
class AttrDTO
{
    const string C_NS = 'namespaces';
    const string C_IS_ENUM = 'isEnum';
    const string C_RENAME_KEYS = 'renameKeys';
    const string C_COLLECTION = 'collection';
    const string C_TRANSFORMER = 'transformerFQCN';
    const string C_PROPERTY = 'property';

    public function __construct(
        public readonly string $dtoFQCN,
        public readonly array $context = []
    ) {}

    public function isEnum(): bool
    {
        return $this->context[static::C_IS_ENUM] ?? false;
    }

    public function isCollection(): bool
    {
        return $this->context[static::C_COLLECTION] ?? false;
    }

    public function namespaces(): array
    {
        return $this->context[static::C_NS] ?? [];
    }

    public function renameKeys(): array
    {
        return $this->context[static::C_RENAME_KEYS] ?? [];
    }

    public function getRenameKey(string $key): ?string
    {
        return $this->context[static::C_RENAME_KEYS][$key] ?? null;
    }

    public function transformerFQCN(): ?string
    {
        return $this->context[static::C_TRANSFORMER] ?? null;
    }

    public function getContextByKey(string $key): mixed
    {
        return $this->context[$key] ?? null;
    }
}