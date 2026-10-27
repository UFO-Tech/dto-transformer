<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Attribute;

use ReflectionParameter;
use ReflectionProperty;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Ufo\DTO\Attributes\AttrAssertions;
use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Helpers\Validator;

/**
 * Перевіряє значення обмеженнями `#[AttrAssertions]`.
 */
class AssertionsValidator
{
    public function __construct(
        protected ValidatorInterface $validator,
    ) {}

    /**
     * @throws BadParamException
     */
    public function validate(AttrAssertions $attribute, mixed $value, ReflectionProperty|ReflectionParameter $reflection): mixed
    {
        $result = Validator::validate($value, $attribute->assertions, validator: $this->validator);

        if ($result->hasErrors()) {
            throw new BadParamException($reflection->getName() . $result->getCurrentError());
        }

        return $value;
    }
}
