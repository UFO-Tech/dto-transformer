<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks\Fixture;

use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;

final class DummyHolder
{
    public function __construct(
        public DummyDTO $item,
    ) {}
}
