<?php

declare(strict_types=1);

namespace Ufo\DTO\Factory;

use Ufo\DTO\Schema\ClassSchemaProviderInterface;
use Ufo\DTO\Transformer\Converter\DateTimeValueConverterInterface;
use Ufo\DTO\Transformer\Normalizer\ArrayNormalizer;
use Ufo\DTO\Transformer\Normalizer\Cache\PropertyContextCache;
use Ufo\DTO\Transformer\Normalizer\DateTimeNormalizer;
use Ufo\DTO\Transformer\Normalizer\DTONormalizer;
use Ufo\DTO\Transformer\Normalizer\EnumNormalizer;
use Ufo\DTO\Transformer\Normalizer\NormalizerChain;
use Ufo\DTO\Transformer\Normalizer\NormalizerChainInterface;
use Ufo\DTO\Transformer\Normalizer\ScalarNormalizer;

class DefaultNormalizerChainFactory implements NormalizerChainFactoryInterface
{
    public function __construct(
        protected ClassSchemaProviderInterface $schemaProvider,
        protected DateTimeValueConverterInterface $dateTimeValueConverter,
    ) {}

    public function create(): NormalizerChainInterface
    {
        return new NormalizerChain([
            new ScalarNormalizer(),
            new EnumNormalizer(),
            new DateTimeNormalizer($this->dateTimeValueConverter),
            new ArrayNormalizer(),
            new DTONormalizer(
                $this->schemaProvider,
                new PropertyContextCache(),
            ),
        ]);
    }
}