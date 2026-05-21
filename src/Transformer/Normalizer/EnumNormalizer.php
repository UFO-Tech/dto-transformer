<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Normalizer;

use BackedEnum;
use Ufo\DTO\Interfaces\Normalizer\PropertyNormalizerInterface;
use Ufo\DTO\VO\NormalizationContext;
use UnitEnum;

class EnumNormalizer implements PropertyNormalizerInterface
{
    public function supports(mixed $data, NormalizationContext $context): bool
    {
        return $data instanceof UnitEnum;
    }

    public function normalize(mixed $data, NormalizationContext $context): string|int
    {
        if (!$data instanceof UnitEnum) {
            throw new \InvalidArgumentException('Data must be an instance of ' . UnitEnum::class);
        }

        return $data instanceof BackedEnum ? $data->value : $data->name;
    }
}
