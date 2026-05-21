<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Fixtures\DTO;

class NormalizerVisibilityDTO
{
    public function __construct(
        public int $id,
        protected string $secret,
    ) {
    }
}
