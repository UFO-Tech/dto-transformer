<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Hydrator;

use DateTimeImmutable;
use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\Transformer\Hydrator\DateTimeHydrator;

class DateTimeHydratorTest extends ParamHydratorTestCase
{
    public function testItSupportsDateTimeClassSchema(): void
    {
        $resolver = $this->resolver(DateTimeHydrator::class);

        $this->assertTrue($resolver->supports([
            TypeHintResolver::CLASS_FQCN => DateTimeImmutable::class,
        ]));
    }

    public function testItResolvesDateTimeValue(): void
    {
        $resolver = $this->resolver(DateTimeHydrator::class);

        $dateTime = $resolver->resolve([
            TypeHintResolver::CLASS_FQCN => DateTimeImmutable::class,
        ], '2026-05-08 14:30:00', $this->context());

        $this->assertInstanceOf(DateTimeImmutable::class, $dateTime);
        $this->assertSame('2026-05-08 14:30:00', $dateTime->format('Y-m-d H:i:s'));
    }
}

