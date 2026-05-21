<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer;

use PHPUnit\Framework\TestCase;
use Ufo\DTO\DTOTransformer;
use Ufo\DTO\ServiceTransformer;
use Ufo\DTO\Transformer\DTOFromArrayTransformer;
use Ufo\DTO\Transformer\DTOToArrayTransformer;
use Ufo\DTO\Transformer\Metadata\ReflectionMetadataKeyGenerator;

final class TransformerStaticInstanceIsolationTest extends TestCase
{
    protected function setUp(): void
    {
        $this->resetTransformerInstances();
    }

    protected function tearDown(): void
    {
        $this->resetTransformerInstances();
    }

    public function testDtoTransformerAndServiceTransformerMaintainSeparateStaticInstances(): void
    {
        $dtoTransformer = $this->createTransformer(DTOTransformer::class, 'dto');
        DTOTransformer::boot($dtoTransformer);

        $this->assertSame($dtoTransformer, $this->dtoTransformerInstance());

        $serviceTransformer = $this->createTransformer(ServiceTransformer::class, 'service');
        ServiceTransformer::boot($serviceTransformer);

        $this->assertSame($dtoTransformer, $this->dtoTransformerInstance());
        $this->assertSame($serviceTransformer, $this->serviceTransformerInstance());
        $this->assertNotSame($dtoTransformer, $serviceTransformer);
        $this->assertNotSame($this->dtoTransformerInstance(), $this->serviceTransformerInstance());

        $dto = DTOTransformer::fromArray(StaticInstanceIsolationDTO::class, ['value' => 'payload']);
        $serviceDto = ServiceTransformer::fromArray(StaticInstanceIsolationDTO::class, ['value' => 'payload']);

        $this->assertInstanceOf(StaticInstanceIsolationDTO::class, $dto);
        $this->assertSame('dto:payload', $dto->value);
        $this->assertInstanceOf(StaticInstanceIsolationDTO::class, $serviceDto);
        $this->assertSame('service:payload', $serviceDto->value);

        $replacementDtoTransformer = $this->createTransformer(DTOTransformer::class, 'dto-replacement');

        $this->assertSame($dtoTransformer, $this->dtoTransformerInstance());
        $this->assertSame($serviceTransformer, $this->serviceTransformerInstance());
        $this->assertNotSame($replacementDtoTransformer, $serviceTransformer);
        $this->assertNotSame($this->dtoTransformerInstance(), $this->serviceTransformerInstance());

        $replacementDto = DTOTransformer::fromArray(StaticInstanceIsolationDTO::class, ['value' => 'payload']);
        $serviceDtoAfterDtoReplacement = ServiceTransformer::fromArray(StaticInstanceIsolationDTO::class, ['value' => 'payload']);

        $this->assertSame('dto:payload', $replacementDto->value);
        $this->assertSame('service:payload', $serviceDtoAfterDtoReplacement->value);

        $this->expectException(\RuntimeException::class);
        DTOTransformer::boot($replacementDtoTransformer);
    }

    /**
     * @param class-string<DTOTransformer> $transformerClass
     */
    private function createTransformer(string $transformerClass, string $valuePrefix): DTOTransformer
    {
        $metadataProvider = new StaticInstanceIsolationReflectionMetadataProvider();

        return new $transformerClass(
            new DTOFromArrayTransformer(
                new StaticInstanceIsolationParamHydratorChain($valuePrefix),
                new StaticInstanceIsolationTypeSchemaResolver(),
                $metadataProvider,
                new ReflectionMetadataKeyGenerator(),
            ),
            new DTOToArrayTransformer(new StaticInstanceIsolationPropertyNormalizerChain()),
        );
    }

    private function dtoTransformerInstance(): DTOTransformer
    {
        return \Closure::bind(
            static fn (): DTOTransformer => DTOTransformer::getInstance(),
            null,
            DTOTransformer::class,
        )();
    }

    private function serviceTransformerInstance(): ServiceTransformer
    {
        return \Closure::bind(
            static fn (): ServiceTransformer => ServiceTransformer::getInstance(),
            null,
            ServiceTransformer::class,
        )();
    }

    private function resetTransformerInstances(): void
    {
        DTOTransformer::reset();
        ServiceTransformer::reset();
    }
}
