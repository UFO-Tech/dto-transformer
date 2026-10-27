<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Hydrator;

use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Schema\Type\TypeHintResolver;
use Ufo\DTO\Transformer\Context\TransformationContext;
use Ufo\DTO\Transformer\Converter\DateTimeValueConverterInterface;

class DateTimeHydrator implements HydratorInterface
{
    public function __construct(
        protected DateTimeValueConverterInterface $converter,
    ) {}

    public function supports(array $schema): bool
    {
        $classFQCN = $schema[TypeHintResolver::CLASS_FQCN] ?? null;

        return is_string($classFQCN) && $this->converter->supported($classFQCN);
    }

    public function resolve(
        array $schema,
        mixed $value,
        TransformationContext $context,
    ): ?object
    {
        if (!is_int($value) && !is_string($value) && !is_float($value) && $value !== null) {
            throw new BadParamException('DateTime value must be int, string, float or null');
        }

        return $this->converter->toObject($value, [
            ...$schema,
            ...$context->namespaces(),
        ]);
    }
}
