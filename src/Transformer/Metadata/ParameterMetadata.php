<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Metadata;

use ReflectionAttribute;
use ReflectionParameter;

class ParameterMetadata
{
    /**
     * @param array<string, string> $declaringNamespaces
     * @param ReflectionAttribute<object>[] $attributes
     */
    public function __construct(
        readonly public ReflectionParameter $reflection,
        readonly public array $declaringNamespaces,
        readonly public DocBlockMetadata $declaringFunctionDocBlock,
        readonly public array $attributes = [],
    ) {}
}

