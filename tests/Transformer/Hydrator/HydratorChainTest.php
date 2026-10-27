<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Hydrator;

use Ufo\DTO\Tests\Support\TransformerFactory;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;
use Ufo\DTO\Transformer\Context\TransformationContext;
use Ufo\DTO\Transformer\Hydrator\HydratorChain;
use Ufo\DTO\Transformer\Hydrator\HydratorInterface;
use Ufo\DTO\Schema\Type\TypeHintResolver;

final class HydratorChainTest extends HydratorTestCase
{
    public function testDelegatesToFirstSupportedResolver(): void
    {
        $result = TransformerFactory::hydratorChain()->resolve(
            [TypeHintResolver::ONE_OFF => [[TypeHintResolver::TYPE => TypeHintResolver::STRING->value], self::dummySchema()]],
            self::DUMMY_DATA,
            $this->context(),
        );

        $this->assertInstanceOf(DummyDTO::class, $result);
        $this->assertSame('Dummy', $result->name);
    }

    public function testCanAddHydratorAfterResolvingSchemaWithoutMatch(): void
    {
        $chain = new HydratorChain();
        $schema = [TypeHintResolver::TYPE => TypeHintResolver::STRING->value];

        $this->assertSame('raw', $chain->resolve($schema, 'raw', $this->context()));

        $chain->addHydrator(new class implements HydratorInterface {
            public function supports(array $schema): bool
            {
                return ($schema[TypeHintResolver::TYPE] ?? null) === TypeHintResolver::STRING->value;
            }

            public function resolve(
                array $schema,
                mixed $value,
                TransformationContext $context,
            ): mixed {
                return 'resolved';
            }
        });

        $this->assertSame('resolved', $chain->resolve($schema, 'raw', $this->context()));
    }
}
