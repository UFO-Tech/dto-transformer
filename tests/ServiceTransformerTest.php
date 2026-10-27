<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests;

use PHPUnit\Framework\TestCase;
use Ufo\DTO\Factory\DefaultDTOTransformerFactory;
use Ufo\DTO\ServiceTransformer;
use Ufo\DTO\Tests\Fixtures\DTO\DateTimeDTO;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;
use Ufo\DTO\Transformer\Converter\DateTimeConverter;

final class ServiceTransformerTest extends TestCase
{
    protected function setUp(): void
    {
        ServiceTransformer::reset();
        ServiceTransformer::boot(DefaultDTOTransformerFactory::default()->create(ServiceTransformer::class));
    }

    protected function tearDown(): void
    {
        ServiceTransformer::reset();
    }

    public function testToArrayReturnsDtoDataWithClassFqcn(): void
    {
        $dto = new DummyDTO(1, 'name');

        $this->assertSame([
            'id' => 1,
            'name' => 'name',
            '$classFQCN' => DummyDTO::class,
        ], ServiceTransformer::toArray($dto, asSmartArray: false));
    }

    public function testToArrayAppliesRenameKey(): void
    {
        $dto = new DummyDTO(1, 'name');

        $this->assertSame([
            'id' => 1,
            'title' => 'name',
            '$classFQCN' => DummyDTO::class,
        ], ServiceTransformer::toArray($dto, ['name' => 'title'], asSmartArray: false));
    }

    public function testToArrayPassesContext(): void
    {
        $dto = ServiceTransformer::fromArray(DateTimeDTO::class, [
            'createdAt' => '2026-05-08 14:30:00',
        ]);

        $this->assertSame([
            'createdAt' => '2026-05-08',
            '$classFQCN' => DateTimeDTO::class,
        ], ServiceTransformer::toArray($dto, asSmartArray: false, context: [
            DateTimeConverter::CONTEXT_OUTPUT_FORMAT => 'Y-m-d',
        ]));
    }

    public function testToArrayResultRoundTripsThroughFromArray(): void
    {
        $dto = new DummyDTO(1, 'name');

        $restored = ServiceTransformer::fromArray(\stdClass::class, ServiceTransformer::toArray($dto));

        $this->assertEquals($dto, $restored);
    }
}
