<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Hydrator;

use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Exceptions\InvalidObjectValueException;
use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\Transformer\Hydrator\AdditionalHydrator;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;

final class AdditionalHydratorTest extends ParamHydratorTestCase
{
    public function testSupportsAdditionalPropertiesSchema(): void
    {
        $this->assertTrue($this->resolver(AdditionalHydrator::class)->supports($this->schema()));
    }

    public function testResolvesEachProperty(): void
    {
        $result = $this->resolver(AdditionalHydrator::class)->resolve(
            $this->schema(),
            ['first' => self::DUMMY_DATA],
            $this->context(),
        );

        $this->assertInstanceOf(DummyDTO::class, $result['first']);
        $this->assertSame('Dummy', $result['first']->name);
    }

    public function testThrowsForNonArrayValue(): void
    {
        $this->expectException(InvalidObjectValueException::class);

        $this->resolver(AdditionalHydrator::class)->resolve(
            $this->schema(),
            'not-array',
            $this->context(),
        );
    }

    public function testKeepsInvalidNestedValueWhenStrictDisabled(): void
    {
        $value = ['first' => ['name' => 'missing-id']];

        $result = $this->resolver(AdditionalHydrator::class)->resolve(
            $this->schema(),
            $value,
            $this->context(strict: false),
        );

        $this->assertSame($value, $result);
    }

    public function testThrowsForInvalidNestedValueWhenStrictEnabled(): void
    {
        $this->expectException(BadParamException::class);

        $this->resolver(AdditionalHydrator::class)->resolve(
            $this->schema(),
            ['first' => ['name' => 'missing-id']],
            $this->context(strict: true),
        );
    }

    private function schema(): array
    {
        return [
            TypeHintResolver::TYPE => TypeHintResolver::OBJECT->value,
            TypeHintResolver::ADDITIONAL_PROPERTIES => self::dummySchema(),
        ];
    }
}
