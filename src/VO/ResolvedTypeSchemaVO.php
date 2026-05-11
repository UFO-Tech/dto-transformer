<?php

declare(strict_types=1);

namespace Ufo\DTO\VO;

readonly class ResolvedTypeSchemaVO
{
    public function __construct(
        public array $schema,
        public bool $fromDocblock,
        public bool $strict,
    ) {}
}
