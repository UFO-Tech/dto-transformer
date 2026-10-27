<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Fixtures\DTO;

use DateTimeImmutable;
use DateTimeInterface;

final class NullableDatesDTO
{
    /** @var DateTimeImmutable|null */
    public $docblock = null;

    /**
     * @param array<DateTimeImmutable|null> $history
     */
    public function __construct(
        public ?DateTimeImmutable $immutable = null,
        public ?DateTimeInterface $interface = null,
        public DateTimeImmutable|null $union = null,
        public ?DateTimeImmutable $withDefault = new DateTimeImmutable('2000-01-01 00:00:00'),
        public array $history = [],
    ) {}
}
