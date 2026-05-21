<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Hydrator;

use ReflectionClass;
use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\Interfaces\Hydrator\ParamHydratorInterface;
use Ufo\DTO\Transformer\Traits\PayloadGuardTrait;
use Ufo\DTO\Transformer\Traits\ValueExtractorTrait;
use Ufo\DTO\VO\TransformationContext;

class ReflectionClassHydrator implements ParamHydratorInterface
{
    use PayloadGuardTrait, ValueExtractorTrait;

    public function supports(array $schema): bool
    {
        $class = $schema[TypeHintResolver::CLASS_FQCN] ?? null;
        return $class === ReflectionClass::class;
    }

    /**
     * @throws BadParamException
     * @throws \ReflectionException
     */
    public function resolve(
        array $schema,
        mixed $value,
        TransformationContext $context,
    ): object
    {
        $payload = $this->requireArray($value);
        $className = $this->requireValue($payload, 'name', TypeHintResolver::STRING);
        return new ReflectionClass($className);
    }
}
