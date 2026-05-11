<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Hydrator;

use ReflectionClass;
use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\Interfaces\Hydrator\ParamHydratorInterface;
use Ufo\DTO\VO\TransformationContext;

class ReflectionClassHydrator implements ParamHydratorInterface
{
    public function supports(array $schema): bool
    {
        $class = $schema[TypeHintResolver::CLASS_FQCN] ?? null;
        return $class === ReflectionClass::class;
    }

    /**
     * @throws BadParamException
     */
    public function resolve(
        array $schema,
        mixed $value,
        TransformationContext $context,
    ): object
    {
        $className = $value['name'] ?? throw new BadParamException('Cannot resolve ReflectionClass without name');
        return new ReflectionClass($className);
    }
}
