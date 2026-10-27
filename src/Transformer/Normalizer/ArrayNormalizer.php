<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Normalizer;

use Ufo\DTO\Transformer\Context\NormalizationContext;

class ArrayNormalizer extends AbstractNormalizer
{
    public function supports(mixed $data, NormalizationContext $context): bool
    {
        return is_array($data);
    }

    public function normalize(mixed $data, NormalizationContext $context): array
    {
        $itemContext = $context->nextDepth();
        return array_map(fn ($value) => $this->chainNormalizer->normalize($value, $itemContext), $data);
    }
}
