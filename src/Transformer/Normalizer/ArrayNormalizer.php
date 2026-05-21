<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Normalizer;

use Ufo\DTO\VO\NormalizationContext;

class ArrayNormalizer extends AbstractNormalizer
{
    public function supports(mixed $data, NormalizationContext $context): bool
    {
        return is_array($data);
    }

    public function normalize(mixed $data, NormalizationContext $context): array
    {
        return array_map(function ($value) use ($context) {
            return $this->chainNormalizer->normalize($value, $context->nextDepth());
        }, $data);
    }
}
