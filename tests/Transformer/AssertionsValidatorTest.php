<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\MetadataInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ContextualValidatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Ufo\DTO\DTOTransformer;
use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Factory\DefaultDTOTransformerFactory;
use Ufo\DTO\ServiceTransformer;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;
use Ufo\DTO\Tests\Fixtures\DTO\ValidDTO;
use Ufo\DTO\Tests\Support\TransformerFactory;
use Ufo\DTO\Transformer\Attribute\AssertionsValidator;
use Ufo\DTO\Transformer\DTOFromArrayTransformer;
use Ufo\DTO\Transformer\DTOFromArrayTransformerInterface;

final class AssertionsValidatorTest extends TestCase
{
    public function testInjectedValidatorChecksEveryAssertion(): void
    {
        $validator = new class (Validation::createValidator()) implements ValidatorInterface {
            public int $calls = 0;

            public function __construct(private ValidatorInterface $inner) {}

            public function getMetadataFor(mixed $value): MetadataInterface
            {
                return $this->inner->getMetadataFor($value);
            }

            public function hasMetadataFor(mixed $value): bool
            {
                return $this->inner->hasMetadataFor($value);
            }

            public function validate(mixed $value, Constraint|array|null $constraints = null, $groups = null): ConstraintViolationListInterface
            {
                $this->calls++;

                return $this->inner->validate($value, $constraints, $groups);
            }

            public function validateProperty(object $object, string $propertyName, $groups = null): ConstraintViolationListInterface
            {
                return $this->inner->validateProperty($object, $propertyName, $groups);
            }

            public function validatePropertyValue(object|string $objectOrClass, string $propertyName, mixed $value, $groups = null): ConstraintViolationListInterface
            {
                return $this->inner->validatePropertyValue($objectOrClass, $propertyName, $value, $groups);
            }

            public function startContext(): ContextualValidatorInterface
            {
                return $this->inner->startContext();
            }

            public function inContext(ExecutionContextInterface $context): ContextualValidatorInterface
            {
                return $this->inner->inContext($context);
            }
        };

        $transformer = $this->transformer($validator);

        $transformer->transformFromArray(ValidDTO::class, ['name' => 'valid']);
        $transformer->transformFromArray(ValidDTO::class, ['title' => 'valid'], ['name' => 'title']);

        $this->assertSame(2, $validator->calls);

        $this->expectException(BadParamException::class);
        $transformer->transformFromArray(ValidDTO::class, ['name' => 'ab']);
    }

    public function testDefaultValidatorIsLazyAndSharedByFacades(): void
    {
        $factory = DefaultDTOTransformerFactory::default();
        $fromArray = static fn (DTOTransformer $facade): DTOFromArrayTransformer
            => (new ReflectionProperty(DTOTransformer::class, 'fromArrayTransformer'))->getValue($facade);
        $validatorOf = static fn (DTOFromArrayTransformer $transformer): object => (new ReflectionProperty(AssertionsValidator::class, 'validator'))
            ->getValue((new ReflectionProperty(DTOFromArrayTransformer::class, 'assertionsValidator'))->getValue($transformer));

        $transformer = $fromArray($factory->create());
        $validator = $validatorOf($transformer);
        $lazy = new ReflectionClass($validator);

        $this->assertSame($validator, $validatorOf($fromArray($factory->create(ServiceTransformer::class))));
        $this->assertTrue($lazy->isUninitializedLazyObject($validator));

        $transformer->transformFromArray(DummyDTO::class, ['id' => 1, 'name' => 'no assertions']);
        $this->assertTrue($lazy->isUninitializedLazyObject($validator));

        $transformer->transformFromArray(ValidDTO::class, ['name' => 'valid']);
        $this->assertFalse($lazy->isUninitializedLazyObject($validator));

        $this->expectException(BadParamException::class);
        $transformer->transformFromArray(ValidDTO::class, ['name' => 'ab']);
    }

    private function transformer(?ValidatorInterface $validator = null): DTOFromArrayTransformerInterface
    {
        return TransformerFactory::fromArrayFactory(validator: $validator)->create();
    }
}
