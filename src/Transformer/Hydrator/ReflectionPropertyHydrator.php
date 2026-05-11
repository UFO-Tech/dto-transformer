<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Hydrator;

use ReflectionProperty;
use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\Interfaces\Hydrator\ParamHydratorInterface;
use Ufo\DTO\VO\TransformationContext;

class ReflectionPropertyHydrator implements ParamHydratorInterface
{
    public function supports(array $schema): bool
    {
        $class = $schema[TypeHintResolver::CLASS_FQCN] ?? null;
        return $class === ReflectionProperty::class;
    }

    /**
     * Expected payload:
     *
     * [
     *     'class' => SomeClass::class,
     *     'property' => 'name',
     * ]
     *
     * @throws BadParamException
     * @throws \ReflectionException
     */
    public function resolve(
        array $schema,
        mixed $value,
        TransformationContext $context,
    ): ReflectionProperty
    {
        $className = $value['class'] ?? null;
        $propertyName = $value['property'] ?? null;

        if (!$className) {
            throw new BadParamException('Cannot resolve ReflectionProperty without class',);
        }

        if (!$propertyName) {
            throw new BadParamException('Cannot resolve ReflectionProperty without property',);
        }

        return new ReflectionProperty($className, $propertyName);
    }
}