<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Hydrator;

use PHPUnit\Framework\TestCase;
use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\Interfaces\Hydrator\ParamHydratorInterface;
use Ufo\DTO\Transformer\Hydrator\AbstractParamHydrator;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;
use Ufo\DTO\Tests\Support\TransformerFactory;
use Ufo\DTO\VO\TransformationContext;

abstract class ParamHydratorTestCase extends TestCase
{
    protected const array DUMMY_DATA = ['id' => 1, 'name' => 'Dummy'];

    protected static function dummySchema(): array
    {
        return [
            TypeHintResolver::TYPE => TypeHintResolver::OBJECT->value,
            TypeHintResolver::ADDITIONAL_PROPERTIES => true,
            TypeHintResolver::CLASS_FQCN => DummyDTO::class,
        ];
    }

    /**
     * @param class-string<ParamHydratorInterface> $resolverFQCN
     */
    protected function resolver(string $resolverFQCN): ParamHydratorInterface
    {
        $resolver = new $resolverFQCN();
        if ($resolver instanceof AbstractParamHydrator) {
            $resolver->setChainHydrator(TransformerFactory::paramHydrator());
        }

        return $resolver;
    }

    /**
     * @param array<string, string>|string[] $namespaces
     */
    protected function context(array $namespaces = [], bool $strict = false): TransformationContext
    {
        return new TransformationContext(
            namespaces: $namespaces,
            strict: $strict,
        );
    }
}
