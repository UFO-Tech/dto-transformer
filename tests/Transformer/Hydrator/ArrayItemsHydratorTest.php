<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Hydrator;

use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Exceptions\InvalidCollectionValueException;
use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\Transformer\Hydrator\ArrayItemsHydrator;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;

final class ArrayItemsHydratorTest extends ParamHydratorTestCase
{
    public function testSupportsArrayItemsSchema(): void
    {
        $this->assertTrue($this->resolver(ArrayItemsHydrator::class)->supports($this->schema()));
    }

    public function testResolvesEachItem(): void
    {
        $result = $this->resolver(ArrayItemsHydrator::class)->resolve(
            $this->schema(),
            [self::DUMMY_DATA],
            $this->context(),
        );

        $this->assertInstanceOf(DummyDTO::class, $result[0]);
        $this->assertSame('Dummy', $result[0]->name);
    }

    public function testKeepsInvalidSmartDtoItem(): void
    {
        $value = [['$className' => 'MissingDTO', ...self::DUMMY_DATA]];

        $result = $this->resolver(ArrayItemsHydrator::class)->resolve(
            [TypeHintResolver::TYPE => TypeHintResolver::ARRAY->value, TypeHintResolver::ITEMS => [TypeHintResolver::TYPE => TypeHintResolver::STRING->value]],
            $value,
            $this->context(),
        );

        $this->assertSame($value, $result);
    }

    public function testThrowsForNonArrayValue(): void
    {
        $this->expectException(InvalidCollectionValueException::class);

        $this->resolver(ArrayItemsHydrator::class)->resolve(
            $this->schema(),
            'not-array',
            $this->context(),
        );
    }

    public function testKeepsInvalidNestedValueWhenStrictDisabled(): void
    {
        $value = [['name' => 'missing-id']];

        $result = $this->resolver(ArrayItemsHydrator::class)->resolve(
            $this->schema(),
            $value,
            $this->context(strict: false),
        );

        $this->assertSame($value, $result);
    }

    public function testThrowsForInvalidNestedValueWhenStrictEnabled(): void
    {
        $this->expectException(BadParamException::class);

        $this->resolver(ArrayItemsHydrator::class)->resolve(
            $this->schema(),
            [['name' => 'missing-id']],
            $this->context(strict: true),
        );
    }

    private function schema(): array
    {
        return [
            TypeHintResolver::TYPE => TypeHintResolver::ARRAY->value,
            TypeHintResolver::ITEMS => self::dummySchema(),
        ];
    }
}
