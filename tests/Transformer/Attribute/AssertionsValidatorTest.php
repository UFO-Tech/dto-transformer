<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Attribute;

use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Ufo\DTO\Attributes\AttrAssertions;
use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Tests\Fixtures\DTO\ValidDTO;
use Ufo\DTO\Transformer\Attribute\AssertionsValidator;

final class AssertionsValidatorTest extends TestCase
{
    public function testValidValueIsReturned(): void
    {
        $property = new ReflectionProperty(ValidDTO::class, 'name');

        $this->assertSame('xxxx', $this->validator()->validate($this->attribute($property), 'xxxx', $property));
    }

    public function testInvalidValueThrowsWithPropertyName(): void
    {
        $property = new ReflectionProperty(ValidDTO::class, 'name');

        $this->expectException(BadParamException::class);
        $this->expectExceptionMessageMatches('/^name: /');

        $this->validator()->validate($this->attribute($property), 'ss', $property);
    }

    public function testInjectedValidatorIsUsed(): void
    {
        $property = new ReflectionProperty(ValidDTO::class, 'name');
        $inner = Validation::createValidator();
        $counting = $this->createMock(ValidatorInterface::class);
        $counting->expects($this->once())
            ->method('validate')
            ->willReturnCallback(static fn (mixed $value, mixed $constraints = null, mixed $groups = null) => $inner->validate($value, $constraints, $groups));

        (new AssertionsValidator($counting))->validate($this->attribute($property), 'xxxx', $property);
    }

    private function validator(): AssertionsValidator
    {
        return new AssertionsValidator(Validation::createValidator());
    }

    private function attribute(ReflectionProperty $property): AttrAssertions
    {
        return $property->getAttributes(AttrAssertions::class)[0]->newInstance();
    }
}
