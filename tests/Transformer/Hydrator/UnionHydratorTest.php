<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Hydrator;

use RuntimeException;
use Ufo\DTO\Exceptions\InvalidObjectValueException;
use Ufo\DTO\Exceptions\UnionTypeMismatchException;
use Ufo\DTO\Schema\Type\TypeHintResolver;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;
use Ufo\DTO\Transformer\Context\TransformationContext;
use Ufo\DTO\Transformer\Hydrator\HydratorChainInterface;
use Ufo\DTO\Transformer\Hydrator\UnionHydrator;

final class UnionHydratorTest extends HydratorTestCase
{
    public function testSupportsOneOfSchema(): void
    {
        $this->assertTrue($this->resolver(UnionHydrator::class)->supports([
            TypeHintResolver::ONE_OFF => [[TypeHintResolver::TYPE => TypeHintResolver::STRING->value]],
        ]));
    }

    public function testResolvesFirstMatchingBranch(): void
    {
        $result = $this->resolver(UnionHydrator::class)->resolve(
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

        $result = $this->resolver(UnionHydrator::class)->resolve(
            [TypeHintResolver::ONE_OFF => [[TypeHintResolver::TYPE => TypeHintResolver::STRING->value], self::dummySchema()]],
            $value,
            $this->context(),
        );

        $this->assertSame($value, $result);
    }

    public function testStrictUnionWithMixedReturnsRawValueWhenNoBranchTransforms(): void
    {
        $value = ['name' => 'missing-id'];

        $result = $this->resolver(UnionHydrator::class)->resolve(
            [
                TypeHintResolver::ONE_OFF => [
                    self::dummySchema(),
                    [TypeHintResolver::ONE_OFF => TypeHintResolver::mixedForJsonSchema()],
                ],
            ],
            $value,
            $this->context(strict: true),
        );

        $this->assertSame($value, $result);
    }

    public function testStrictUnionThrowsMismatchExceptionWhenNoBranchMatches(): void
    {
        $this->expectException(UnionTypeMismatchException::class);
        $this->expectExceptionMessage('Value does not match any union type branch.');

        $this->resolver(UnionHydrator::class)->resolve(
            [TypeHintResolver::ONE_OFF => [[TypeHintResolver::TYPE => TypeHintResolver::STRING->value], self::dummySchema()]],
            ['name' => 'missing-id'],
            $this->context(strict: true),
        );
    }

    public function testDoesNotCatchRuntimeErrorsFromBranchHydration(): void
    {
        $hydrator = new UnionHydrator();
        $hydrator->setChainHydrator(new class implements HydratorChainInterface {
            public function resolve(
                array $schema,
                mixed $value,
                TransformationContext $context,
            ): mixed {
                throw new RuntimeException('runtime failure');
            }
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('runtime failure');

        $hydrator->resolve(
            [TypeHintResolver::ONE_OFF => [[TypeHintResolver::TYPE => TypeHintResolver::STRING->value]]],
            'value',
            $this->context(strict: true),
        );
    }

    public function testNullableDtoResolvesNullInAnyContext(): void
    {
        $schema = [TypeHintResolver::ONE_OFF => [self::dummySchema(), [TypeHintResolver::TYPE => TypeHintResolver::NULL->value]]];
        $hydrator = $this->resolver(UnionHydrator::class);

        $this->assertNull($hydrator->resolve($schema, null, $this->context()));
        $this->assertNull($hydrator->resolve($schema, null, $this->context(strict: true)));
        $this->assertInstanceOf(DummyDTO::class, $hydrator->resolve($schema, self::DUMMY_DATA, $this->context(strict: true)));
    }

    public function testPicksBranchByValueType(): void
    {
        $schema = [
            TypeHintResolver::ONE_OFF => [
                [TypeHintResolver::TYPE => TypeHintResolver::INTEGER->value],
                [TypeHintResolver::TYPE => TypeHintResolver::STRING->value],
                self::dummySchema(),
            ],
        ];
        $hydrator = $this->resolver(UnionHydrator::class);

        $this->assertSame(5, $hydrator->resolve($schema, 5, $this->context(strict: true)));
        $this->assertSame('x', $hydrator->resolve($schema, 'x', $this->context(strict: true)));
        $this->assertInstanceOf(DummyDTO::class, $hydrator->resolve($schema, self::DUMMY_DATA, $this->context(strict: true)));
    }

    public function testStrictMismatchKeepsLastBranchErrorAsPrevious(): void
    {
        try {
            $this->resolver(UnionHydrator::class)->resolve(
                [TypeHintResolver::ONE_OFF => [[TypeHintResolver::TYPE => TypeHintResolver::STRING->value], self::dummySchema()]],
                5,
                $this->context(strict: true),
            );
            $this->fail('Expected UnionTypeMismatchException');
        } catch (UnionTypeMismatchException $e) {
            $this->assertInstanceOf(InvalidObjectValueException::class, $e->getPrevious());
            $this->assertSame('Cannot assign int to DTO ' . DummyDTO::class, $e->getPrevious()->getMessage());
        }
    }
}
