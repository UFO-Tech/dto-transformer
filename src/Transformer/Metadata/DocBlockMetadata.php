<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Metadata;

use phpDocumentor\Reflection\DocBlock;

class DocBlockMetadata
{
    public function __construct(
        readonly public ?string $raw,
        readonly public ?DocBlock $docBlock = null,
    ) {}

    public function isEmpty(): bool
    {
        return $this->docBlock === null;
    }

    public function getTagsByName(string $name): array
    {
        return $this->docBlock?->getTagsByName($name) ?? [];
    }
}

