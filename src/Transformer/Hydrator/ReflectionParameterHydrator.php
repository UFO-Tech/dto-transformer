<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Hydrator;

use ReflectionException;
use ReflectionParameter;
use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\Interfaces\Hydrator\ParamHydratorInterface;
use Ufo\DTO\VO\TransformationContext;

class ReflectionParameterHydrator implements ParamHydratorInterface
{
    public function supports(array $schema): bool
    {
        $class = $schema[TypeHintResolver::CLASS_FQCN] ?? null;
        return $class === ReflectionParameter::class;
    }

    /**
     * Expected payload:
     *
     * [
     *     'class' => SomeClass::class,
     *     'method' => '__construct',
     *     'parameter' => 'name',
     * ]
     *
     * or
     *
     * [
     *     'function' => 'myFunction',
     *     'parameter' => 'name',
     * ]
     *
     * @throws BadParamException|ReflectionException
     */
    public function resolve(
        array $schema,
        mixed $value,
        TransformationContext $context,
    ): ReflectionParameter
    {
        $parameterName = $value['parameter'] ?? null;

        if (!$parameterName) {
            throw new BadParamException('Missing reflection parameter name');
        }

        if (isset($value['class'], $value['method'])) {
            return new ReflectionParameter(
                [$value['class'], $value['method']],
                $parameterName,
            );
        }

        if (isset($value['function'])) {
            return new ReflectionParameter(
                $value['function'],
                $parameterName,
            );
        }

        throw new BadParamException('Cannot resolve ReflectionParameter without callable definition');
    }
}