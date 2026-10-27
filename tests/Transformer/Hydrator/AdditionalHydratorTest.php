<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Hydrator;

use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Exceptions\InvalidObjectValueException;
use Ufo\DTO\Transformer\Hydrator\AdditionalHydrator;
use Ufo\DTO\Schema\Type\TypeHintResolver;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO2;
use Ufo\DTO\Tests\Support\TransformerFactory;

final class AdditionalHydratorTest extends HydratorTestCase
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

    public function testSmartObjectSchemasFromPayloadAreNotCached(): void
    {
        $chain = TransformerFactory::hydratorChain();
        $hydrator = new AdditionalHydrator();
        $hydrator->setChainHydrator($chain);
        $schema = [
            TypeHintResolver::TYPE => TypeHintResolver::OBJECT->value,
            TypeHintResolver::ADDITIONAL_PROPERTIES => true,
        ];
        $cache = new \ReflectionProperty($chain, 'nodeBySchema');

        $first = $hydrator->resolve($schema, ['$className' => DummyDTO::class] + self::DUMMY_DATA, $this->context());
        $before = count($cache->getValue($chain));
        $second = $hydrator->resolve($schema, ['$className' => DummyDTO2::class] + self::DUMMY_DATA, $this->context());

        for ($i = 0; $i < 50; $i++) {
            try {
                $hydrator->resolve($schema, ['$className' => 'NoSuch\\Payload' . $i] + self::DUMMY_DATA, $this->context());
            } catch (\Throwable) {
            }
        }

        $this->assertInstanceOf(DummyDTO::class, $first);
        $this->assertInstanceOf(DummyDTO2::class, $second);
        $this->assertSame($before, count($cache->getValue($chain)));
    }

    private function schema(): array
    {
        return [
            TypeHintResolver::TYPE => TypeHintResolver::OBJECT->value,
            TypeHintResolver::ADDITIONAL_PROPERTIES => self::dummySchema(),
        ];
    }
}
