<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Hydrator;

use PHPUnit\Framework\TestCase;
use Ufo\DTO\Schema\Type\TypeHintResolver;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;
use Ufo\DTO\Tests\Support\TransformerFactory;
use Ufo\DTO\Transformer\Context\TransformationContext;
use Ufo\DTO\Transformer\Hydrator\AbstractHydrator;
use Ufo\DTO\Transformer\Hydrator\HydratorInterface;

abstract class HydratorTestCase extends TestCase
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
     * @param class-string<HydratorInterface> $resolverFQCN
     */
    protected function resolver(string $resolverFQCN): HydratorInterface
    {
        $resolver = new $resolverFQCN();
        if ($resolver instanceof AbstractHydrator) {
            $resolver->setChainHydrator(TransformerFactory::hydratorChain());
        }

        return $resolver;
    }

    /**
     * @param array<string, string>|string[] $namespaces
     */
    protected function context(array $namespaces = [], bool $strict = false): TransformationContext
    {
        return TransformationContext::fromArray(
            namespaces: $namespaces,
            strict: $strict,
        );
    }
}
