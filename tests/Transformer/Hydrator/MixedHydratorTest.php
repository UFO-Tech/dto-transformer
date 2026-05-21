<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Hydrator;

use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\Transformer\Hydrator\MixedHydrator;

final class MixedHydratorTest extends ParamHydratorTestCase
{
    public function testSupportsAnySchema(): void
    {
        $this->assertTrue($this->resolver(MixedHydrator::class)->supports([
            TypeHintResolver::TYPE => TypeHintResolver::ANY->value,
        ]));
    }

    public function testSupportsMixedSchema(): void
    {
        $this->assertTrue($this->resolver(MixedHydrator::class)->supports([
            TypeHintResolver::TYPE => TypeHintResolver::MIXED->value,
        ]));
    }

    public function testKeepsRawValue(): void
    {
        $value = ['any' => ['shape']];

        $result = $this->resolver(MixedHydrator::class)->resolve(
            [TypeHintResolver::TYPE => TypeHintResolver::ANY->value],
            $value,
            $this->context(),
        );

        $this->assertSame($value, $result);
    }
}
