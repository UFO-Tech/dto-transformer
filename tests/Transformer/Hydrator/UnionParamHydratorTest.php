<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Hydrator;

use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\Transformer\Hydrator\UnionParamHydrator;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;

final class UnionParamHydratorTest extends ParamHydratorTestCase
{
    public function testSupportsOneOfSchema(): void
    {
        $this->assertTrue($this->resolver(UnionParamHydrator::class)->supports([
            TypeHintResolver::ONE_OFF => [[TypeHintResolver::TYPE => TypeHintResolver::STRING->value]],
        ]));
    }

    public function testResolvesFirstMatchingBranch(): void
    {
        $result = $this->resolver(UnionParamHydrator::class)->resolve(
            [TypeHintResolver::ONE_OFF => [[TypeHintResolver::TYPE => TypeHintResolver::STRING->value], self::dummySchema()]],
            self::DUMMY_DATA,
            $this->context(),
        );

        $this->assertInstanceOf(DummyDTO::class, $result);
        $this->assertSame('Dummy', $result->name);
    }

    public function testReturnsRawValueWhenNoBranchMatches(): void
    {
        $value = ['name' => 'missing-id'];

        $result = $this->resolver(UnionParamHydrator::class)->resolve(
            [TypeHintResolver::ONE_OFF => [[TypeHintResolver::TYPE => TypeHintResolver::STRING->value], self::dummySchema()]],
            $value,
            $this->context(),
        );

        $this->assertSame($value, $result);
    }
}
