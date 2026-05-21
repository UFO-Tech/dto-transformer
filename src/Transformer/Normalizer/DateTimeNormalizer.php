<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Normalizer;

use DateTimeInterface;
use Ufo\DTO\Interfaces\Converter\DateTimeValueConverterInterface;
use Ufo\DTO\Interfaces\Normalizer\PropertyNormalizerInterface;
use Ufo\DTO\Transformer\Converter\DateTimeConverter;
use Ufo\DTO\VO\NormalizationContext;

class DateTimeNormalizer implements PropertyNormalizerInterface
{
    public function __construct(
        protected ?DateTimeValueConverterInterface $dateTimeValueConverter = null,
    ) {
        $this->dateTimeValueConverter ??= new DateTimeConverter();
    }

    public function supports(mixed $data, NormalizationContext $context): bool
    {
        return $data instanceof DateTimeInterface;
    }

    public function normalize(mixed $data, NormalizationContext $context): string|int|float|bool|null
    {
        return $this->dateTimeValueConverter->toScalar($data, $context->values());
    }
}
