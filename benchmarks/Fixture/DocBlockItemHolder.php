<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks\Fixture;

final class DocBlockItemHolder
{
    /**
     * @param Item $item
     */
    public function __construct(
        public mixed $item = null,
    ) {}
}
