<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Hydrator;

use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\Transformer\Hydrator\ScalarParamHydrator;

final class ScalarParamHydratorTest extends ParamHydratorTestCase
{
    public function testSupportsScalarSchema(): void
    {
        $this->assertTrue($this->resolver(ScalarParamHydrator::class)->supports([
            TypeHintResolver::TYPE => TypeHintResolver::STRING->value,
        ]));
    }

    public function testKeepsMatchingValue(): void
    {
        $result = $this->resolver(ScalarParamHydrator::class)->resolve(
            [TypeHintResolver::TYPE => TypeHintResolver::INTEGER->value],
            10,
            $this->context(),
        );

        $this->assertSame(10, $result);
    }

    public function testThrowsForMismatchedValueWhenStrictEnabled(): void
    {
        $this->expectException(BadParamException::class);

        $this->resolver(ScalarParamHydrator::class)->resolve(
            [TypeHintResolver::TYPE => TypeHintResolver::INTEGER->value],
            'not-int',
            $this->context(strict: true),
        );
    }

    public function testKeepsMismatchedValueWhenStrictDisabled(): void
    {
        $result = $this->resolver(ScalarParamHydrator::class)->resolve(
            [TypeHintResolver::TYPE => TypeHintResolver::INTEGER->value],
            'not-int',
            $this->context(strict: false),
        );

        $this->assertSame('not-int', $result);
    }

    public function testReturnsNullForNullSchema(): void
    {
        $result = $this->resolver(ScalarParamHydrator::class)->resolve(
            [TypeHintResolver::TYPE => TypeHintResolver::NULL->value],
            null,
            $this->context(),
        );

        $this->assertNull($result);
    }

    public function testThrowsForNonNullValueOnNullSchema(): void
    {
        $this->expectException(BadParamException::class);

        $this->resolver(ScalarParamHydrator::class)->resolve(
            [TypeHintResolver::TYPE => TypeHintResolver::NULL->value],
            'not-null',
            $this->context(),
        );
    }

    public function testObjectBranchKeepsArrayValue(): void
    {
        $value = ['raw' => true];

        $result = $this->resolver(ScalarParamHydrator::class)->resolve(
            [TypeHintResolver::TYPE => TypeHintResolver::OBJECT->value],
            $value,
            $this->context(),
        );

        $this->assertSame($value, $result);
    }

    public function testObjectBranchThrowsForNonArrayValue(): void
    {
        $this->expectException(BadParamException::class);

        $this->resolver(ScalarParamHydrator::class)->resolve(
            [TypeHintResolver::TYPE => TypeHintResolver::OBJECT->value],
            'not-object-array',
            $this->context(),
        );
    }
}
