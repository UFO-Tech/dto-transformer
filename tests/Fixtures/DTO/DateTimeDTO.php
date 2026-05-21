<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Fixtures\DTO;

use DateTimeImmutable;

class DateTimeDTO
{
    public function __construct(
        public DateTimeImmutable $createdAt,
    ) {}
}

