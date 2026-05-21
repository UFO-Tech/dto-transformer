<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Metadata;

use ReflectionAttribute;
use ReflectionClass;

class ClassMetadata
{
    /**
     * @param array<string, string> $namespaces
     * @param ReflectionAttribute<object>[] $attributes
     */
    public function __construct(
        readonly public ReflectionClass $reflection,
        readonly public array $namespaces,
        readonly public DocBlockMetadata $docBlock,
        readonly public array $attributes = [],
    ) {}
}

