<?php

declare(strict_types=1);

namespace Ufo\DTO\Schema\Type;

readonly class ResolvedTypeSchema
{
    public function __construct(
        public array $schema,
        public bool $fromDocblock,
        public bool $strict,
    ) {}
}
