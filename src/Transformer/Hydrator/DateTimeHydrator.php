<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Hydrator;

use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\Interfaces\Converter\DateTimeValueConverterInterface;
use Ufo\DTO\Interfaces\Hydrator\ParamHydratorInterface;
use Ufo\DTO\Transformer\Converter\DateTimeConverter;
use Ufo\DTO\VO\TransformationContext;

class DateTimeHydrator implements ParamHydratorInterface
{
    public function __construct(
        protected ?DateTimeValueConverterInterface $converter = null,
    ) {
        $this->converter ??= new DateTimeConverter();
    }

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
            ...$context->classes,
        ]);
    }
}
