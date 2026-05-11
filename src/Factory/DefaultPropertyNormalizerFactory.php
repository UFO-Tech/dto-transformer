<?php

declare(strict_types=1);

namespace Ufo\DTO\Factory;

use Ufo\DTO\Interfaces\Converter\DateTimeValueConverterInterface;
use Ufo\DTO\Interfaces\Factory\PropertyNormalizerFactoryInterface;
use Ufo\DTO\Interfaces\Meta\ReflectionMetadataProviderInterface;
use Ufo\DTO\Interfaces\Normalizer\PropertyNormalizerChainInterface;
use Ufo\DTO\Interfaces\Normalizer\SerializationContextProviderInterface;
use Ufo\DTO\Transformer\Normalizer\ArrayNormalizer;
use Ufo\DTO\Transformer\Normalizer\DateTimeNormalizer;
use Ufo\DTO\Transformer\Normalizer\DtoNormalizer;
use Ufo\DTO\Transformer\Normalizer\EnumNormalizer;
use Ufo\DTO\Transformer\Normalizer\PropertyNormalizer;
use Ufo\DTO\Transformer\Normalizer\ScalarValueConverter;

class DefaultPropertyNormalizerFactory implements PropertyNormalizerFactoryInterface
{
    public function __construct(
        protected ReflectionMetadataProviderInterface $metadataProvider,
        protected SerializationContextProviderInterface $serializationContextProvider,
        protected DateTimeValueConverterInterface $dateTimeValueConverter,
    ) {}

    public function create(): PropertyNormalizerChainInterface
    {
        return new PropertyNormalizer([
            new ScalarValueConverter(),
            new EnumNormalizer(),
            new DateTimeNormalizer($this->dateTimeValueConverter),
            new ArrayNormalizer(),
            new DtoNormalizer(
                $this->metadataProvider,
                $this->serializationContextProvider,
            ),
        ]);
    }
}