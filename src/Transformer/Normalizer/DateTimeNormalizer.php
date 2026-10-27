<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Normalizer;

use DateTimeInterface;
use Ufo\DTO\Transformer\Context\NormalizationContext;
use Ufo\DTO\Transformer\Converter\DateTimeValueConverterInterface;

class DateTimeNormalizer implements NormalizerInterface
{
    public function __construct(
        protected DateTimeValueConverterInterface $dateTimeValueConverter,
    ) {}

    public function supports(mixed $data, NormalizationContext $context): bool
    {
        return $data instanceof DateTimeInterface;
    }

    public function normalize(mixed $data, NormalizationContext $context): string|int|float|bool|null
    {
        return $this->dateTimeValueConverter->toScalar($data, $context->values());
    }
}
