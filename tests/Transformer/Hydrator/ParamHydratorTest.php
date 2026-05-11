<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Hydrator;

use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\Interfaces\Hydrator\ParamHydratorInterface;
use Ufo\DTO\Tests\Support\TransformerFactory;
use Ufo\DTO\Transformer\Hydrator\ParamHydrator;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;
use Ufo\DTO\VO\TransformationContext;

final class ParamHydratorTest extends ParamHydratorTestCase
{
    public function testDelegatesToFirstSupportedResolver(): void
    {
        $result = TransformerFactory::paramHydrator()->resolve(
            [TypeHintResolver::ONE_OFF => [[TypeHintResolver::TYPE => TypeHintResolver::STRING->value], self::dummySchema()]],
            self::DUMMY_DATA,
            $this->context(),
        );

        $this->assertInstanceOf(DummyDTO::class, $result);
        $this->assertSame('Dummy', $result->name);
    }

    public function testCanAddHydratorAfterResolvingSchemaWithoutMatch(): void
    {
        $paramHydrator = new ParamHydrator();
        $schema = [TypeHintResolver::TYPE => TypeHintResolver::STRING->value];

        $this->assertSame('raw', $paramHydrator->resolve($schema, 'raw', $this->context()));

        $paramHydrator->addHydrator(new class implements ParamHydratorInterface {
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

        $this->assertSame('resolved', $paramHydrator->resolve($schema, 'raw', $this->context()));
    }
}
