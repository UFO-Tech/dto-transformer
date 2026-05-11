<?php

declare(strict_types=1);

namespace Ufo\DTO\Annotations;

readonly class StrictModeTag
{
    public function __construct(
        public bool $enabled = true,
    ) {}
}
