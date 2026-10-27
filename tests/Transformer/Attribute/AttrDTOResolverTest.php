<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Attribute;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Ufo\DTO\Attributes\AttrDTO;
use Ufo\DTO\DTOTransformer;
use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Exceptions\NotSupportDTOException;
use Ufo\DTO\Tests\Fixtures\CountingUpperTransformer;
use Ufo\DTO\Tests\Fixtures\CustomAttrDTO;
use Ufo\DTO\Tests\Fixtures\DTO\AssertedAttrDtoCollectionDTO;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;
use Ufo\DTO\Tests\Fixtures\DTO\MemberDto;
use Ufo\DTO\Tests\Fixtures\DTO\MemberWithFriendsDTO;
use Ufo\DTO\Tests\Fixtures\DTO\NullableAttrDTO;
use Ufo\DTO\Tests\Fixtures\DTO\RequiredAttrDTO;
use Ufo\DTO\Tests\Fixtures\DTO\UserDto;
use Ufo\DTO\Tests\Fixtures\DTO\ValidDTO;
use Ufo\DTO\Tests\Fixtures\DTO\ValueErrorHolderDTO;
use Ufo\DTO\Tests\Fixtures\NamespaceCapturingTransformer;
use Ufo\DTO\Tests\Fixtures\NoInterfaceTransformer;
use Ufo\DTO\Tests\Fixtures\UpperTransformer;
use Ufo\DTO\Tests\Fixtures\ValueErrorTransformer;
use Ufo\DTO\Tests\Support\InitDTOTransformerTrait;
use Ufo\DTO\Tests\Support\TransformerFactory;
use Ufo\DTO\Transformer\Attribute\AttrDTOResolver;
use Ufo\DTO\Transformer\Context\TransformationContext;
use Ufo\DTO\Transformer\DTOFromContextTransformerInterface;

use function time;

final class AttrDTOResolverTest extends TestCase
{
    use InitDTOTransformerTrait;

    public function testResolvesSingleDto(): void
    {
        $user = $this->resolve(
            new AttrDTO(MemberDto::class),
            ['name' => 'testName1', 'email' => 'testEmail1', 'currentTime' => time()],
            new ReflectionProperty(MemberWithFriendsDTO::class, 'user'),
        );

        $this->assertInstanceOf(MemberDto::class, $user);
        $this->assertSame('testName1', $user->name);
        $this->assertSame('testEmail1', $user->email);
    }

    public function testResolvesCollection(): void
    {
        $result = $this->resolve(
            new AttrDTO(MemberDto::class, context: [AttrDTO::C_COLLECTION => true]),
            [
                ['name' => 'testName1', 'email' => 'testEmail1', 'currentTime' => time()],
                ['name' => 'testName2', 'email' => 'testEmail2', 'currentTime' => time()],
            ],
            new ReflectionProperty(MemberWithFriendsDTO::class, 'friends'),
        );

        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(MemberDto::class, $result);
        $this->assertSame('testName2', $result[1]->name);
    }

    public function testLooseCollectionKeepsItemThatFails(): void
    {
        $data = [
            ['name' => 'testName1', 'email' => 'testEmail1', 'currentTime' => time()],
            ['name' => 'missingEmail'],
        ];

        $result = $this->resolve(
            new AttrDTO(MemberDto::class, context: [AttrDTO::C_COLLECTION => true]),
            $data,
            new ReflectionProperty(MemberWithFriendsDTO::class, 'friends'),
        );

        $this->assertInstanceOf(MemberDto::class, $result[0]);
        $this->assertSame($data[1], $result[1]);
    }

    public function testAttributeSubclassResolvesCollection(): void
    {
        $result = $this->resolve(
            new CustomAttrDTO(DummyDTO::class, context: [AttrDTO::C_COLLECTION => true, AttrDTO::C_STRICT => true]),
            [['id' => 1, 'name' => 'a'], ['id' => 2, 'name' => 'b']],
            new ReflectionProperty(DummyDTO::class, 'name'),
        );

        $this->assertContainsOnlyInstancesOf(DummyDTO::class, $result);
        $this->assertSame('b', $result[1]->name);
    }

    public function testResolvesSmartClassName(): void
    {
        $result = $this->resolve(
            new AttrDTO(DummyDTO::class),
            ['$className' => UserDto::class, 'id' => 12, 'name' => 'smart-user', 'email' => 'smart@example.com', 'currentTime' => 123],
            new ReflectionProperty(DummyDTO::class, 'name'),
        );

        $this->assertInstanceOf(UserDto::class, $result);
        $this->assertSame('smart-user', $result->name);
    }

    public function testCustomTransformerByInterface(): void
    {
        $result = $this->resolve(
            new AttrDTO(ValidDTO::class, context: [AttrDTO::C_TRANSFORMER => UpperTransformer::class]),
            ['name' => 'lower_case'],
            new ReflectionProperty(ValidDTO::class, 'name'),
        );

        $this->assertInstanceOf(ValidDTO::class, $result);
        $this->assertSame('LOWER_CASE', $result->name);
    }

    public function testCustomTransformerThatDoesNotSupportClassThrowsWithItsName(): void
    {
        $this->expectException(NotSupportDTOException::class);
        $this->expectExceptionMessage(UpperTransformer::class . ' is not support transform for ' . DummyDTO::class);

        $this->resolve(
            new AttrDTO(DummyDTO::class, context: [AttrDTO::C_TRANSFORMER => UpperTransformer::class]),
            ['name' => 'invalid_case'],
            new ReflectionProperty(DummyDTO::class, 'name'),
        );
    }

    public function testCustomTransformerWithoutInterfaceThrows(): void
    {
        $this->expectException(NotSupportDTOException::class);
        $this->expectExceptionMessage('Transformer ' . NoInterfaceTransformer::class . ' must implement');

        $this->resolve(
            new AttrDTO(ValidDTO::class, context: [AttrDTO::C_TRANSFORMER => NoInterfaceTransformer::class]),
            ['name' => 'abc'],
            new ReflectionProperty(ValidDTO::class, 'name'),
        );
    }

    public function testCustomTransformerReceivesNamespaces(): void
    {
        NamespaceCapturingTransformer::$namespaces = [];

        $this->resolve(
            new AttrDTO(ValidDTO::class, context: [
                AttrDTO::C_TRANSFORMER => NamespaceCapturingTransformer::class,
                AttrDTO::C_NS => ['Item' => 'Attr\\Item'],
            ]),
            ['name' => 'abc'],
            new ReflectionProperty(ValidDTO::class, 'name'),
            TransformationContext::fromArray(namespaces: ['Caller' => 'Caller\\Ns']),
        );

        $this->assertSame(['Caller' => 'Caller\\Ns', 'Item' => 'Attr\\Item'], NamespaceCapturingTransformer::$namespaces);
    }

    public function testCollectionPreparesCustomTransformerOnce(): void
    {
        CountingUpperTransformer::$supportChecks = 0;

        $result = $this->resolve(
            new AttrDTO(ValidDTO::class, context: [
                AttrDTO::C_COLLECTION => true,
                AttrDTO::C_STRICT => true,
                AttrDTO::C_TRANSFORMER => CountingUpperTransformer::class,
            ]),
            [['name' => 'abc'], ['name' => 'def'], ['name' => 'ghi']],
            new ReflectionProperty(ValidDTO::class, 'name'),
        );

        $this->assertSame('GHI', $result[2]->name);
        $this->assertSame(1, CountingUpperTransformer::$supportChecks);
    }

    public function testValueErrorFromCustomTransformerKeepsOriginalReason(): void
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('inner reason from transformer');

        $this->resolve(
            new AttrDTO(DummyDTO::class, context: [AttrDTO::C_TRANSFORMER => ValueErrorTransformer::class]),
            ['id' => 1, 'name' => 'n'],
            new ReflectionProperty(DummyDTO::class, 'name'),
        );
    }

    public function testValueErrorFromAttributeProcessingReachesCallerOfFromArray(): void
    {
        $this->expectException(BadParamException::class);
        $this->expectExceptionMessage('inner reason from transformer');

        DTOTransformer::fromArray(ValueErrorHolderDTO::class, ['item' => ['id' => 1, 'name' => 'n']]);
    }

    public function testAssertionsAndAttrDtoOnOnePropertyBothApply(): void
    {
        $dto = DTOTransformer::fromArray(AssertedAttrDtoCollectionDTO::class, [
            'items' => [['id' => 1, 'name' => 'a'], ['id' => 2, 'name' => 'b']],
        ]);

        $this->assertCount(2, $dto->items);
        $this->assertContainsOnlyInstancesOf(DummyDTO::class, $dto->items);
        $this->assertSame('b', $dto->items[1]->name);
    }

    public function testAssertionsRejectValueBeforeAttrDtoTransformsIt(): void
    {
        $this->expectException(BadParamException::class);
        $this->expectExceptionMessage('items: This collection should contain 2 elements or more.');

        DTOTransformer::fromArray(AssertedAttrDtoCollectionDTO::class, ['items' => [['id' => 1, 'name' => 'a']]]);
    }

    public function testEmptyArrayForRequiredValidatedPropertyThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ValidDTO::fromArray([]);
    }

    public function testExplicitNullForNullableAttrDtoPropertiesStaysNull(): void
    {
        $dto = DTOTransformer::fromArray(NullableAttrDTO::class, [
            'single' => null,
            'items' => null,
            'strictItems' => null,
            'untyped' => null,
        ]);

        $this->assertNull($dto->single);
        $this->assertNull($dto->items);
        $this->assertNull($dto->strictItems);
        $this->assertNull($dto->untyped);
    }

    public function testNullableAttrDtoPropertiesSurviveRoundTrip(): void
    {
        $dto = DTOTransformer::fromArray(NullableAttrDTO::class, [
            'single' => ['id' => 1, 'name' => 'one'],
        ]);

        $again = DTOTransformer::fromArray(NullableAttrDTO::class, DTOTransformer::toArray($dto));

        $this->assertEquals($dto, $again);
        $this->assertSame('one', $again->single->name);
        $this->assertNull($again->items);
    }

    public function testExplicitNullForNonNullableAttrDtoPropertyThrows(): void
    {
        $this->expectException(BadParamException::class);
        $this->expectExceptionMessage('Cannot assign null to non-nullable single');

        DTOTransformer::fromArray(RequiredAttrDTO::class, ['single' => null]);
    }

    public function testExplicitNullForNonNullableAttrDtoCollectionThrows(): void
    {
        $this->expectException(BadParamException::class);
        $this->expectExceptionMessage('Cannot assign null to non-nullable items');

        DTOTransformer::fromArray(RequiredAttrDTO::class, ['items' => null]);
    }

    public function testNonIterableValueForStrictAttrDtoCollectionThrows(): void
    {
        $this->expectException(BadParamException::class);
        $this->expectExceptionMessage('expects an iterable, string given');

        DTOTransformer::fromArray(NullableAttrDTO::class, ['strictItems' => 'not-a-list']);
    }

    public function testNonIterableValueForLooseAttrDtoCollectionIsKeptRawWithoutWarning(): void
    {
        $warnings = [];
        set_error_handler(static function (int $errno, string $message) use (&$warnings): bool {
            $warnings[] = $message;

            return true;
        });

        try {
            $dto = DTOTransformer::fromArray(NullableAttrDTO::class, ['looseAny' => 'not-a-list']);
        } finally {
            restore_error_handler();
        }

        $this->assertSame('not-a-list', $dto->looseAny);
        $this->assertSame([], $warnings);
    }

    public function testNonIterableValueForLooseTypedAttrDtoCollectionThrows(): void
    {
        $this->expectException(BadParamException::class);

        DTOTransformer::fromArray(RequiredAttrDTO::class, ['items' => 'not-a-list']);
    }

    private function resolve(
        AttrDTO $attribute,
        mixed $value,
        ReflectionProperty $property,
        ?TransformationContext $context = null,
    ): mixed {
        $transformer = TransformerFactory::fromArrayTransformer();
        $this->assertInstanceOf(DTOFromContextTransformerInterface::class, $transformer);

        return (new AttrDTOResolver())->resolve($attribute, $value, $property, $transformer, $context ?? TransformationContext::fromArray());
    }
}
