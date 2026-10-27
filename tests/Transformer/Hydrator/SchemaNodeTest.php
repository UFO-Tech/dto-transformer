<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Hydrator;

use PHPUnit\Framework\TestCase;
use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Exceptions\UnionTypeMismatchException;
use Ufo\DTO\Schema\Type\TypeHintResolver;
use Ufo\DTO\Transformer\Context\TransformationContext;
use Ufo\DTO\Transformer\Hydrator\AdditionalHydrator;
use Ufo\DTO\Transformer\Hydrator\ArrayItemsHydrator;
use Ufo\DTO\Transformer\Hydrator\Cache\CachedHydratorChain;
use Ufo\DTO\Transformer\Hydrator\HydratorChain;
use Ufo\DTO\Transformer\Hydrator\HydratorInterface;
use Ufo\DTO\Transformer\Hydrator\ScalarHydrator;
use Ufo\DTO\Transformer\Hydrator\UnionHydrator;

final class SchemaNodeTest extends TestCase
{
    private const string COUNTED = 'counted';

    public function testNestedItemsDoNotSelectHydratorPerValue(): void
    {
        $counter = $this->countingHydrator();
        $chain = $this->countingChain($counter);
        $schema = [
            TypeHintResolver::TYPE => TypeHintResolver::ARRAY->value,
            TypeHintResolver::ITEMS => [
                TypeHintResolver::TYPE => TypeHintResolver::OBJECT->value,
                TypeHintResolver::ADDITIONAL_PROPERTIES => [TypeHintResolver::TYPE => self::COUNTED],
            ],
        ];
        $value = array_fill(0, 50, ['a' => 1, 'b' => 2]);

        $chain->resolve($schema, $value, TransformationContext::fromArray());
        $chain->keys = 0;
        $result = $chain->resolve($schema, $value, TransformationContext::fromArray());

        $this->assertSame(1, $chain->keys);
        $this->assertSame(200, $counter->calls);
        $this->assertSame(['a' => 2, 'b' => 3], $result[49]);
    }

    public function testSameSubSchemaCompilesToSharedNode(): void
    {
        $chain = $this->countingChain($this->countingHydrator());
        $item = [TypeHintResolver::TYPE => self::COUNTED];

        $array = $chain->node([TypeHintResolver::TYPE => TypeHintResolver::ARRAY->value, TypeHintResolver::ITEMS => $item]);
        $union = $chain->node([TypeHintResolver::ONE_OFF => [$item, [TypeHintResolver::TYPE => TypeHintResolver::NULL->value]]]);

        $this->assertSame($array->items, $union->branches[0]);
        $this->assertTrue($union->allowsNull);
        $this->assertFalse($array->allowsNull);
    }

    public function testStrictUnionMismatchRunsEachBranchOnce(): void
    {
        $counter = $this->countingHydrator(fail: true);
        $chain = $this->countingChain($counter);
        $schema = [
            TypeHintResolver::ONE_OFF => [
                [TypeHintResolver::TYPE => self::COUNTED, 'branch' => 1],
                [TypeHintResolver::TYPE => TypeHintResolver::INTEGER->value],
                [TypeHintResolver::TYPE => self::COUNTED, 'branch' => 2],
            ],
        ];

        try {
            $chain->resolve($schema, 'text', TransformationContext::fromArray(strict: true));
            $this->fail('Union mismatch expected');
        } catch (UnionTypeMismatchException $exception) {
            $this->assertSame('branch 2', $exception->getPrevious()?->getMessage());
        }

        $this->assertSame(2, $counter->calls);
    }

    public function testNonStrictUnionSkipsRejectedBranchesOnFailure(): void
    {
        $counter = $this->countingHydrator(fail: true);
        $chain = $this->countingChain($counter);
        $schema = [
            TypeHintResolver::ONE_OFF => [
                [TypeHintResolver::TYPE => TypeHintResolver::INTEGER->value],
                [TypeHintResolver::TYPE => self::COUNTED, 'branch' => 1],
            ],
        ];

        $this->assertSame('text', $chain->resolve($schema, 'text', TransformationContext::fromArray()));
        $this->assertSame(1, $counter->calls);
    }

    private function countingChain(HydratorInterface $counter): HydratorChain
    {
        $chain = new class extends CachedHydratorChain {
            public int $keys = 0;

            protected function schemaKey(array $schema): string
            {
                $this->keys++;

                return parent::schemaKey($schema);
            }
        };

        return $chain->addHydrators([
            new UnionHydrator(),
            new ScalarHydrator(),
            new ArrayItemsHydrator(),
            new AdditionalHydrator(),
            $counter,
        ]);
    }

    private function countingHydrator(bool $fail = false): HydratorInterface
    {
        return new class ($fail) implements HydratorInterface {
            public int $calls = 0;

            public function __construct(private readonly bool $fail) {}

            public function supports(array $schema): bool
            {
                return ($schema[TypeHintResolver::TYPE] ?? null) === 'counted';
            }

            public function resolve(array $schema, mixed $value, TransformationContext $context): mixed
            {
                $this->calls++;
                if ($this->fail) {
                    throw new BadParamException('branch ' . $schema['branch']);
                }

                return $value + 1;
            }
        };
    }
}
