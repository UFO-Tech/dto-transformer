<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Hydrator;

use ReflectionProperty;
use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\Interfaces\Hydrator\ParamHydratorInterface;
use Ufo\DTO\Transformer\Traits\PayloadGuardTrait;
use Ufo\DTO\Transformer\Traits\ValueExtractorTrait;
use Ufo\DTO\VO\TransformationContext;

class ReflectionPropertyHydrator implements ParamHydratorInterface
{
    use PayloadGuardTrait, ValueExtractorTrait;

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
        $payload = $this->requireArray($value);

        $className = $this->requireValue($payload, 'class', TypeHintResolver::STRING);
        $propertyName = $this->requireValue($payload, 'property', TypeHintResolver::STRING);

        return new ReflectionProperty($className, $propertyName);
    }
}