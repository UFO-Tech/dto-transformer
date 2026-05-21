<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Hydrator;

use ReflectionException;
use ReflectionParameter;
use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\Interfaces\Hydrator\ParamHydratorInterface;
use Ufo\DTO\Transformer\Traits\PayloadGuardTrait;
use Ufo\DTO\Transformer\Traits\ValueExtractorTrait;
use Ufo\DTO\VO\TransformationContext;

class ReflectionParameterHydrator implements ParamHydratorInterface
{
    use PayloadGuardTrait, ValueExtractorTrait;

    public function supports(array $schema): bool
    {
        return ($schema[TypeHintResolver::CLASS_FQCN] ?? null) === ReflectionParameter::class;
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
     * @throws BadParamException
     * @throws ReflectionException
     */
    public function resolve(
        array $schema,
        mixed $value,
        TransformationContext $context,
    ): ReflectionParameter
    {
        $payload = $this->requireArray($value);

        $parameterName = $this->requireValue($payload, 'parameter', TypeHintResolver::STRING);

        if (isset($payload['class'], $payload['method'])) {
            return new ReflectionParameter(
                [
                    $this->requireValue($payload, 'class', TypeHintResolver::STRING),
                    $this->requireValue($payload, 'method', TypeHintResolver::STRING),
                ],
                $parameterName,
            );
        }

        if (isset($payload['function'])) {
            return new ReflectionParameter(
                $this->requireValue($payload, 'function', TypeHintResolver::STRING),
                $parameterName,
            );
        }

        throw new BadParamException('Cannot resolve ReflectionParameter without callable definition');
    }
}