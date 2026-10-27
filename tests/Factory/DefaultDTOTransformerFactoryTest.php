<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Factory;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Ufo\DTO\DTOTransformer;
use Ufo\DTO\Factory\DefaultDTOTransformerFactory;
use Ufo\DTO\Factory\DefaultDTOTransformerFromArrayFactory;
use Ufo\DTO\ServiceTransformer;
use Ufo\DTO\Tests\Fixtures\DTO\DateTimeDTO;
use Ufo\DTO\Tests\Support\TransformerFactory;
use Ufo\DTO\Transformer\Converter\DateTimeConverter;
use Ufo\DTO\Transformer\DTOFromArrayTransformer;
use Ufo\DTO\Transformer\DTOToArrayTransformer;
use Ufo\DTO\Transformer\Normalizer\DTONormalizer;
use Ufo\DTO\Transformer\Normalizer\NormalizerChain;

final class DefaultDTOTransformerFactoryTest extends TestCase
{
    public function testFacadesFromOneFactoryShareTransformerGraph(): void
    {
        $factory = DefaultDTOTransformerFactory::default();

        $dto = $factory->create();
        $service = $factory->create(ServiceTransformer::class);

        $this->assertInstanceOf(ServiceTransformer::class, $service);
        $this->assertNotSame($dto, $service);
        $this->assertSame($this->inner($dto, 'fromArrayTransformer'), $this->inner($service, 'fromArrayTransformer'));
        $this->assertSame($this->inner($dto, 'toArrayTransformer'), $this->inner($service, 'toArrayTransformer'));
    }

    public function testFromArrayAndToArrayShareClassSchemaProvider(): void
    {
        $dto = DefaultDTOTransformerFactory::default()->create();
        $chain = (new ReflectionProperty(DTOToArrayTransformer::class, 'propertyNormalizer'))->getValue($this->inner($dto, 'toArrayTransformer'));
        $converters = (new ReflectionProperty(NormalizerChain::class, 'converters'))->getValue($chain);
        $dtoNormalizer = array_values(array_filter($converters, static fn ($converter) => $converter instanceof DTONormalizer))[0];

        $this->assertSame(
            (new ReflectionProperty(DTOFromArrayTransformer::class, 'schemaProvider'))->getValue($this->inner($dto, 'fromArrayTransformer')),
            (new ReflectionProperty(DTONormalizer::class, 'schemaProvider'))->getValue($dtoNormalizer),
        );
    }

    public function testFactoriesDoNotShareGraph(): void
    {
        $first = DefaultDTOTransformerFactory::default()->create();
        $second = DefaultDTOTransformerFactory::default()->create();

        $this->assertNotSame($this->inner($first, 'fromArrayTransformer'), $this->inner($second, 'fromArrayTransformer'));
    }

    public function testDateTimeConverterIsUsedForHydration(): void
    {
        $converter = new class extends DateTimeConverter {
            public function toObject(int|string|float|null $value, array $context = [], ?callable $callback = null): ?object
            {
                return new DateTimeImmutable('2000-01-01 00:00:00');
            }
        };
        [$typeSchemaResolver, $metadataProvider, $schemaProvider, $validator] = TransformerFactory::fromArrayFactoryArguments();

        $dto = (new DefaultDTOTransformerFromArrayFactory($typeSchemaResolver, $metadataProvider, $schemaProvider, $validator, $converter))
            ->create()
            ->transformFromArray(DateTimeDTO::class, ['createdAt' => '2026-05-08 14:30:00']);

        $this->assertSame('2000-01-01', $dto->createdAt->format('Y-m-d'));
    }

    private function inner(DTOTransformer $facade, string $property): object
    {
        return (new ReflectionProperty(DTOTransformer::class, $property))->getValue($facade);
    }
}
