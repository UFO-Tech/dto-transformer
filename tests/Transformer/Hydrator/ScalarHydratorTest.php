<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Hydrator;

use Ufo\DTO\Exceptions\InvalidScalarValueException;
use Ufo\DTO\Schema\Type\TypeHintResolver;
use Ufo\DTO\Transformer\Hydrator\ScalarHydrator;

final class ScalarHydratorTest extends HydratorTestCase
{
    public function testSupportsScalarSchema(): void
    {
        $this->assertTrue($this->resolver(ScalarHydrator::class)->supports([
            TypeHintResolver::TYPE => TypeHintResolver::STRING->value,
        ]));
    }

    public function testKeepsMatchingValue(): void
    {
        $result = $this->resolver(ScalarHydrator::class)->resolve(
            [TypeHintResolver::TYPE => TypeHintResolver::INTEGER->value],
            10,
            $this->context(),
        );

        $this->assertSame(10, $result);
    }

    public function testThrowsForMismatchedValueWhenStrictEnabled(): void
    {
        $this->expectException(InvalidScalarValueException::class);

        $this->resolver(ScalarHydrator::class)->resolve(
            [TypeHintResolver::TYPE => TypeHintResolver::INTEGER->value],
            'not-int',
            $this->context(strict: true),
        );
    }

    public function testKeepsMismatchedValueWhenStrictDisabled(): void
    {
        $result = $this->resolver(ScalarHydrator::class)->resolve(
            [TypeHintResolver::TYPE => TypeHintResolver::INTEGER->value],
            'not-int',
            $this->context(strict: false),
        );

        $this->assertSame('not-int', $result);
    }

    public function testReturnsNullForNullSchema(): void
    {
        $result = $this->resolver(ScalarHydrator::class)->resolve(
            [TypeHintResolver::TYPE => TypeHintResolver::NULL->value],
            null,
            $this->context(),
        );

        $this->assertNull($result);
    }

    public function testThrowsForNonNullValueOnNullSchema(): void
    {
        $this->expectException(InvalidScalarValueException::class);

        $this->resolver(ScalarHydrator::class)->resolve(
            [TypeHintResolver::TYPE => TypeHintResolver::NULL->value],
            'not-null',
            $this->context(strict: true),
        );
    }

    public function testDoesNotSupportObjectSchema(): void
    {
        $this->assertFalse($this->resolver(ScalarHydrator::class)->supports([
            TypeHintResolver::TYPE => TypeHintResolver::OBJECT->value,
        ]));
    }
}
