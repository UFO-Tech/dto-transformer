<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Fixtures\DTO;

use DateTimeImmutable;
use DateTimeInterface;

final class RequiredDatesDTO
{
    public function __construct(
        public DateTimeImmutable $immutable,
        public DateTimeInterface $interface,
    ) {}
}
