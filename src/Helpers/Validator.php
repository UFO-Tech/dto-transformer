<?php

namespace Ufo\DTO\Helpers;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Ufo\DTO\Exceptions\BadParamException;

class Validator
{

    protected function __construct(
        protected ValidatorInterface $validator,
        protected mixed $data,
        protected ConstraintViolationListInterface $errors
    ) {}

    public static function validate(mixed $value, null|Constraint|array $constraints = null, null|string|GroupSequence|array $groups = null): static
    {
        $validator = Validation::createValidator();
        $errors = $validator->validate($value, $constraints, $groups);
        return new static($validator, $value, $errors);
    }

    /**
     * @return mixed
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * @return ConstraintViolationListInterface
     */
    public function getErrors(): ConstraintViolationListInterface
    {
        return $this->errors;
    }

    /**
     * @return bool
     */
    public function hasErrors(): bool
    {
        return $this->errors->count() > 0;
    }

    /**
     * @return string
     */
    public function getCurrentError(): string
    {
        $error = $this->errors[0];
        return $error->getPropertyPath() . ': ' . $error->getMessage();
    }

    /**
     * @param string $class
     * @return void
     * @throws BadParamException
     */
    public function throw(string $class = BadParamException::class): void
    {
        if ($this->hasErrors()) {
            throw new BadParamException($this->getCurrentError());
        }
    }
}
