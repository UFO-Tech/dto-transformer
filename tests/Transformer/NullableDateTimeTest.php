<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer;

use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use Ufo\DTO\DTOTransformer;
use Ufo\DTO\Tests\Fixtures\DTO\NullableDatesDTO;
use Ufo\DTO\Tests\Fixtures\DTO\RequiredDatesDTO;
use Ufo\DTO\Tests\Support\InitDTOTransformerTrait;
use Ufo\DTO\Tests\Support\TransformerFactory;

final class NullableDateTimeTest extends TestCase
{
    use InitDTOTransformerTrait;

    public function testExplicitNullForNullableDateStaysNull(): void
    {
        $dto = TransformerFactory::fromArrayTransformer()->transformFromArray(NullableDatesDTO::class, [
            'immutable' => null,
            'interface' => null,
            'union' => null,
            'withDefault' => null,
            'docblock' => null,
        ]);

        $this->assertNull($dto->immutable);
        $this->assertNull($dto->interface);
        $this->assertNull($dto->union);
        $this->assertNull($dto->withDefault);
        $this->assertNull($dto->docblock);
    }

    public function testNullItemsInNullableDateCollectionStayNull(): void
    {
        $dto = TransformerFactory::fromArrayTransformer()->transformFromArray(NullableDatesDTO::class, [
            'history' => [null, '2026-05-08 14:30:00'],
        ]);

        $this->assertNull($dto->history[0]);
        $this->assertInstanceOf(DateTimeImmutable::class, $dto->history[1]);
        $this->assertSame('2026-05-08 14:30:00', $dto->history[1]->format('Y-m-d H:i:s'));
    }

    public function testNullableDateStillHydratesValues(): void
    {
        $dto = TransformerFactory::fromArrayTransformer()->transformFromArray(NullableDatesDTO::class, [
            'immutable' => '2026-05-08 14:30:00',
            'interface' => 1746700000,
            'union' => '2026-01-01 00:00:00',
        ]);

        $this->assertSame('2026-05-08 14:30:00', $dto->immutable?->format('Y-m-d H:i:s'));
        $this->assertSame(1746700000, $dto->interface?->getTimestamp());
        $this->assertSame('2026-01-01 00:00:00', $dto->union?->format('Y-m-d H:i:s'));
    }

    public function testMissingKeyForNullableDateUsesDefault(): void
    {
        $dto = TransformerFactory::fromArrayTransformer()->transformFromArray(NullableDatesDTO::class, []);

        $this->assertNull($dto->immutable);
        $this->assertSame('2000-01-01', $dto->withDefault?->format('Y-m-d'));
    }

    public function testExplicitNullForRequiredDateIsNow(): void
    {
        $before = time();
        $dto = TransformerFactory::fromArrayTransformer()->transformFromArray(RequiredDatesDTO::class, [
            'immutable' => null,
            'interface' => null,
        ]);
        $after = time();

        $this->assertInstanceOf(DateTimeImmutable::class, $dto->immutable);
        $this->assertInstanceOf(DateTimeInterface::class, $dto->interface);
        $this->assertGreaterThanOrEqual($before, $dto->immutable->getTimestamp());
        $this->assertLessThanOrEqual($after, $dto->immutable->getTimestamp());
        $this->assertGreaterThanOrEqual($before, $dto->interface->getTimestamp());
        $this->assertLessThanOrEqual($after, $dto->interface->getTimestamp());
    }

    public function testNullDateSurvivesRoundTrip(): void
    {
        $dto = DTOTransformer::fromArray(NullableDatesDTO::class, ['immutable' => null, 'union' => '2026-01-01 00:00:00']);

        $again = DTOTransformer::fromArray(NullableDatesDTO::class, DTOTransformer::toArray($dto));

        $this->assertNull($again->immutable);
        $this->assertNull($again->interface);
        $this->assertSame('2026-01-01 00:00:00', $again->union?->format('Y-m-d H:i:s'));
    }
}
