<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Fixtures\DTO;

use DateTimeImmutable;
use DateTimeInterface;
use Ufo\DTO\Attributes\SerializationContext;
use Ufo\DTO\Transformer\Converter\DateTimeConverter;
use Ufo\DTO\Tests\Fixtures\Enum\StringEnum;
use Ufo\DTO\VO\NormalizationContext;

class SerializationContextDTO
{
    public function __construct(
        #[SerializationContext([NormalizationContext::DATE_FORMAT => DateTimeInterface::ATOM])]
        public DateTimeImmutable $createdAt,
        public StringEnum $status,
        #[SerializationContext([DateTimeConverter::CONTEXT_OUTPUT_FORMAT => 'Y-m-d'])]
        public DateTimeImmutable $publishedAt,
    ) {
    }
}
