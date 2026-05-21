<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Normalizer;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Ufo\DTO\BaseDTOFromArrayTransformer;
use Ufo\DTO\Interfaces\Normalizer\PropertyNormalizerChainInterface;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;
use Ufo\DTO\Tests\Fixtures\DTO\NormalizerVisibilityDTO;
use Ufo\DTO\Tests\Fixtures\DTO\SerializationContextDTO;
use Ufo\DTO\Tests\Fixtures\DTO\WrapperDTO;
use Ufo\DTO\Tests\Fixtures\Enum\StringEnum;
use Ufo\DTO\Tests\Support\TransformerFactory;
use Ufo\DTO\VO\NormalizationContext;

final class DtoNormalizerTest extends TestCase
{
    public function testNormalizesPublicDtoProperties(): void
    {
        $result = $this->normalizer()->normalize(
            new DummyDTO(1, 'Test'),
            NormalizationContext::create(),
        );

        $this->assertSame([
            'id' => 1,
            'name' => 'Test',
        ], $result);
    }

    public function testNormalizesNestedDtoCollectionsThroughChain(): void
    {
        $result = $this->normalizer()->normalize(
            new WrapperDTO([
                new DummyDTO(1, 'First'),
                new DummyDTO(2, 'Second'),
            ]),
            NormalizationContext::create(),
        );

        $this->assertSame([
            'items' => [
                ['id' => 1, 'name' => 'First'],
                ['id' => 2, 'name' => 'Second'],
            ],
        ], $result);
    }

    public function testSupportsRenameKeysAndSmartArrayContext(): void
    {
        $result = $this->normalizer()->normalize(
            new DummyDTO(7, 'Renamed'),
            NormalizationContext::create(
                renameKey: ['name' => 'title'],
                asSmartArray: true,
            ),
        );

        $this->assertSame([
            'id' => 7,
            'title' => 'Renamed',
            BaseDTOFromArrayTransformer::DTO_CLASSNAME => DummyDTO::class,
        ], $result);
    }

    public function testPublicOnlyContextFiltersNonPublicProperties(): void
    {
        $result = $this->normalizer()->normalize(
            new NormalizerVisibilityDTO(10, 'hidden'),
            NormalizationContext::create(publicOnly: true),
        );

        $this->assertSame(['id' => 10], $result);
    }

    public function testCanIncludeNonPublicPropertiesWhenPublicOnlyDisabled(): void
    {
        $result = $this->normalizer()->normalize(
            new NormalizerVisibilityDTO(10, 'hidden'),
            NormalizationContext::create(publicOnly: false),
        );

        $this->assertSame([
            'id' => 10,
            'secret' => 'hidden',
        ], $result);
    }

    public function testAppliesPropertySerializationContext(): void
    {
        $result = $this->normalizer()->normalize(
            new SerializationContextDTO(
                new DateTimeImmutable('2026-05-08 14:30:00+03:00'),
                StringEnum::B,
                new DateTimeImmutable('2026-05-09 10:00:00+03:00'),
            ),
            NormalizationContext::create(),
        );

        $this->assertSame([
            'createdAt' => '2026-05-08T14:30:00+03:00',
            'status' => 'b',
            'publishedAt' => '2026-05-09',
        ], $result);
    }

    protected function normalizer(): PropertyNormalizerChainInterface
    {
        return TransformerFactory::propertyNormalizer();
    }
}
