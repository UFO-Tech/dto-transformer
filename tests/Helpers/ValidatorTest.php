<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Helpers;

use DomainException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validation;
use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Helpers\Validator;

final class ValidatorTest extends TestCase
{
    public function testValidateUsesPassedValidator(): void
    {
        $custom = Validation::createValidator();
        $validator = new ReflectionProperty(Validator::class, 'validator');

        $this->assertSame($custom, $validator->getValue(Validator::validate('x', [new NotBlank()], validator: $custom)));
    }

    public function testValidatorKeepsErrorsPerCall(): void
    {
        $shared = Validation::createValidator();

        $invalid = Validator::validate('ab', [new NotBlank(), new Length(min: 3)], validator: $shared);
        $valid = Validator::validate('abc', [new NotBlank(), new Length(min: 3)], validator: $shared);

        $this->assertTrue($invalid->hasErrors());
        $this->assertCount(1, $invalid->getErrors());
        $this->assertFalse($valid->hasErrors());
        $this->assertCount(1, $invalid->getErrors());
    }

    public function testThrowUsesPassedExceptionClass(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(': This value should not be blank.');

        Validator::validate('', [new NotBlank()])->throw(DomainException::class);
    }

    public function testThrowDefaultsToBadParamException(): void
    {
        $this->expectException(BadParamException::class);

        Validator::validate('', [new NotBlank()])->throw();
    }

    public function testThrowDoesNothingWithoutErrors(): void
    {
        Validator::validate('x', [new NotBlank()])->throw(DomainException::class);

        $this->addToAssertionCount(1);
    }
}
