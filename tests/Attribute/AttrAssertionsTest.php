<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Attribute;

use PHPUnit\Framework\TestCase;
use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Tests\Fixtures\DTO\AssertedDefaultsDTO;
use Ufo\DTO\Tests\Fixtures\DTO\ValidDTO;
use Ufo\DTO\Tests\Support\TransformerFactory;

final class AttrAssertionsTest extends TestCase
{
    public function testMissingOptionalKeyUsesDefaultWithoutValidation(): void
    {
        $dto = TransformerFactory::fromArrayTransformer()->transformFromArray(AssertedDefaultsDTO::class, []);

        $this->assertSame('default', $dto->optional);
        $this->assertSame('present', $dto->notNull);
    }

    public function testMissingOptionalKeyWithRenameKeyUsesDefaultWithoutValidation(): void
    {
        $dto = TransformerFactory::fromArrayTransformer()->transformFromArray(
            AssertedDefaultsDTO::class,
            [],
            ['optional' => 'opt'],
        );

        $this->assertSame('default', $dto->optional);
    }

    public function testMissingRequiredKeyReportsMissingKeyInsteadOfValidationError(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Missing required key for property: 'name'");

        TransformerFactory::fromArrayTransformer()->transformFromArray(ValidDTO::class, []);
    }

    public function testExplicitNullIsValidatedAsNull(): void
    {
        $this->expectException(BadParamException::class);
        $this->expectExceptionMessage('notNull: This value should not be null.');

        TransformerFactory::fromArrayTransformer()->transformFromArray(AssertedDefaultsDTO::class, ['notNull' => null]);
    }

    public function testPresentInvalidValueIsStillRejected(): void
    {
        $this->expectException(BadParamException::class);
        $this->expectExceptionMessage('optional: This value is too short.');

        TransformerFactory::fromArrayTransformer()->transformFromArray(AssertedDefaultsDTO::class, ['optional' => 'ab']);
    }

    public function testPresentValidValueIsHydrated(): void
    {
        $dto = TransformerFactory::fromArrayTransformer()->transformFromArray(AssertedDefaultsDTO::class, ['optional' => 'abcd', 'notNull' => 'x']);

        $this->assertSame('abcd', $dto->optional);
        $this->assertSame('x', $dto->notNull);
    }
}
