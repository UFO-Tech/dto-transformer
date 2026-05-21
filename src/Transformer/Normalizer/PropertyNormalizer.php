<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Normalizer;

use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Interfaces\Normalizer\PropertyNormalizerChainInterface;
use Ufo\DTO\Interfaces\Normalizer\PropertyNormalizerInterface;
use Ufo\DTO\VO\NormalizationContext;

class PropertyNormalizer implements PropertyNormalizerChainInterface
{
    /**
     * @param iterable<PropertyNormalizerInterface> $converters
     */
    public function __construct(
        protected iterable $converters = [],
    ) {
        foreach ($this->converters as $converter) {
            if ($converter instanceof AbstractNormalizer) {
                $converter->setChainNormalizer($this);
            }
        }
    }

    public function supports(mixed $data, NormalizationContext $context): bool
    {
        foreach ($this->converters as $converter) {
            if ($converter->supports($data, $context)) {
                return true;
            }
        }

        return false;
    }

    public function normalize(mixed $data, NormalizationContext $context): mixed
    {
        foreach ($this->converters as $converter) {
            if ($converter->supports($data, $context)) {
                return $converter->normalize($data, $context);
            }
        }
        throw new BadParamException('Primitive value converter not found for "' . get_debug_type($data) . '"');
    }
}
