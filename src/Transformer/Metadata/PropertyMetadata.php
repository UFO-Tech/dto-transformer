<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Metadata;

use ReflectionAttribute;
use ReflectionProperty;

class PropertyMetadata
{
    /**
     * @param array<string, string> $declaringNamespaces
     * @param ReflectionAttribute<object>[] $attributes
     */
    public function __construct(
        readonly public ReflectionProperty $reflection,
        readonly public array $declaringNamespaces,
        readonly public DocBlockMetadata $docBlock,
        readonly public array $attributes = [],
        readonly public ?ParameterMetadata $promotedConstructorParameter = null,
    ) {}
}

