<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Hydrator;

use Ufo\DTO\Exceptions\InvalidScalarValueException;
use Ufo\DTO\Schema\Type\TypeHintResolver;
use Ufo\DTO\Transformer\Context\TransformationContext;
use function in_array;
use function sprintf;

class ScalarHydrator implements HydratorInterface
{
    public function supports(array $schema): bool
    {
        return TypeHintResolver::isScalarSchema($schema);
    }

    public function resolve(
        array $schema,
        mixed $value,
        TransformationContext $context,
    ): mixed
    {
        $type = $schema[TypeHintResolver::TYPE];

        $runtimeType = TypeHintResolver::tryFrom(TypeHintResolver::jsonSchemaToPhp($type));
        if ($runtimeType?->matchType($value) ?? false) {
            return $value;
        }

        if ($context->isStrict()) {
            throw new InvalidScalarValueException(sprintf('Normalizer does not match schema type %s', $type));
        }
        return $value;
    }
}
