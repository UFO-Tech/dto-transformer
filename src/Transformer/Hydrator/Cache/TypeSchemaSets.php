<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Hydrator\Cache;

use Ufo\DTO\Schema\CompiledTypeSchema;

/**
 * Скомпільовані схеми типів однієї схеми класу: перший набір namespaces окремо, решта — з лімітом.
 */
final class TypeSchemaSets
{
    public ?string $fingerprint = null;

    /**
     * @var array<int, ?CompiledTypeSchema>
     */
    public array $schemas = [];

    /**
     * @var array<string, array<int, ?CompiledTypeSchema>>
     */
    public array $sets = [];
}
