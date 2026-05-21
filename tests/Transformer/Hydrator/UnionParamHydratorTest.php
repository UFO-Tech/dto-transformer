<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Hydrator;

use RuntimeException;
use Ufo\DTO\Exceptions\UnionTypeMismatchException;
use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\Interfaces\Hydrator\ParamHydratorChainInterface;
use Ufo\DTO\Transformer\Hydrator\UnionParamHydrator;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;
use Ufo\DTO\VO\TransformationContext;

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

    public function testStrictUnionWithMixedReturnsRawValueWhenNoBranchTransforms(): void
    {
        $value = ['name' => 'missing-id'];

        $result = $this->resolver(UnionParamHydrator::class)->resolve(
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

        $this->resolver(UnionParamHydrator::class)->resolve(
            [TypeHintResolver::ONE_OFF => [[TypeHintResolver::TYPE => TypeHintResolver::STRING->value], self::dummySchema()]],
            ['name' => 'missing-id'],
            $this->context(strict: true),
        );
    }

    public function testDoesNotCatchRuntimeErrorsFromBranchHydration(): void
    {
        $hydrator = new UnionParamHydrator();
        $hydrator->setChainHydrator(new class implements ParamHydratorChainInterface {
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
}
