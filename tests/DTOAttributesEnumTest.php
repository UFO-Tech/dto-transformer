<?php

namespace Ufo\DTO\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\RequiresMethod;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;
use Ufo\DTO\Attributes\AttrDTO;
use Ufo\DTO\DTOAttributesEnum;
use Ufo\DTO\DTOTransformer;
use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Exceptions\NotSupportDTOException;
use Ufo\DTO\Tests\Fixtures\AttrNoParent;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;
use Ufo\DTO\Tests\Fixtures\DTO\MemberDto;
use Ufo\DTO\Tests\Fixtures\DTO\MemberWithFriendsDTO;
use Ufo\DTO\Tests\Fixtures\DTO\UserDto;
use Ufo\DTO\Tests\Fixtures\DTO\ValidDTO;
use Ufo\DTO\Tests\Fixtures\UpperTransformer;

class DTOAttributesEnumTest extends TestCase
{
    public function testCases(): void
    {

        $property = new ReflectionProperty(UserDto::class, 'name');
        $method = new ReflectionMethod(self::class, 'getMemberDto');
        $attribute = $method->getAttributes()[0];

        $result = DTOAttributesEnum::tryFromAttr($attribute, ["name" => 'ok', 'email' => 'q'], $property, DTOTransformer::class);
        $this->assertSame('ok', $result->name);
    }

    public function testValidateThrows(): void
    {
        $property = new ReflectionProperty(ValidDTO::class, 'name');
        $attribute = $property->getAttributes()[0];

        $res = DTOAttributesEnum::tryFromAttr($attribute, 'xxxx', $property, DTOTransformer::class);
        $this->assertSame('xxxx', $res);
        $this->expectException(BadParamException::class);
        DTOAttributesEnum::tryFromAttr($attribute, 'ss', $property, DTOTransformer::class);
    }

    public function testTransformDto(): void
    {
        $property = new ReflectionProperty(MemberWithFriendsDTO::class, 'user');
        $method = new ReflectionMethod(self::class, 'getMemberDto');
        $attribute = $method->getAttributes()[0];

        $data =
            ['name' => 'testName1', 'email' => 'testEmail1']
        ;

        $user = DTOAttributesEnum::tryFromAttr($attribute, $data, $property, DTOTransformer::class);

        $this->assertInstanceOf(MemberDto::class, $user);
        $this->assertSame('testName1', $user->name);
        $this->assertSame('testEmail1', $user->email);
    }

    public function testTransformDtoCollection(): void
    {
        $property = new ReflectionProperty(MemberWithFriendsDTO::class, 'friends');
        $method = new ReflectionMethod(self::class, 'getMemberDtoCollection');
        $attribute = $method->getAttributes()[0];

        $data = [
            ['name' => 'testName1', 'email' => 'testEmail1'],
            ['name' => 'testName2', 'email' => 'testEmail2'],
        ];

        $result = DTOAttributesEnum::tryFromAttr($attribute, $data, $property, DTOTransformer::class);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(MemberDto::class, $result);
        $this->assertSame('testName2', $result[1]->name);
    }


    #[AttrDTO(MemberDto::class)]
    protected function getMemberDto(): UserDto
    {
        return new UserDto('testName', 'testEmail');
    }

    #[AttrDTO(MemberDto::class, collection: true)]
    protected function getMemberDtoCollection(): array
    {
        return [
            new UserDto('testName1', 'testEmail1'),
            new UserDto('testName2', 'testEmail2'),
        ];
    }

    #[AttrDTO(ValidDTO::class, transformerFQCN: UpperTransformer::class)]
    protected function getCustomTransformerValidDTO(): array
    {
        return ['name' => 'lower_case'];
    }

    #[AttrDTO(DummyDTO::class, transformerFQCN: UpperTransformer::class)]
    protected function getCustomTransformerInvalidDTO(): array
    {
        return ['name' => 'invalid_case'];
    }

    #[AttrNoParent()]
    protected function getNoSupport(): array
    {
        return ['name' => 'no_support'];
    }

    public function testTransformByCustomTransformerByInterface(): void
    {
        $property = new ReflectionProperty(ValidDTO::class, 'name');
        $method = new ReflectionMethod(self::class, 'getCustomTransformerValidDTO');
        $attribute = $method->getAttributes()[0];

        $result = DTOAttributesEnum::tryFromAttr($attribute, $this->getCustomTransformerValidDTO(), $property, DTOTransformer::class);

        $this->assertInstanceOf(ValidDTO::class, $result);
        $this->assertSame('LOWER_CASE', $result->name);
    }

    public function testFallbackByUnsupportedTransformerByInterface(): void
    {
        $property = new ReflectionProperty(DummyDTO::class, 'name');
        $method= new ReflectionMethod(self::class, 'getCustomTransformerInvalidDTO');
        $attribute = $method->getAttributes()[0];

        $this->expectException(NotSupportDTOException::class);
        DTOAttributesEnum::tryFromAttr($attribute, $this->getCustomTransformerInvalidDTO(), $property, DTOTransformer::class);
    }

    public function testTryFromAttrThrowsWhenNoParentMatch(): void
    {
        $property = new \ReflectionProperty(DummyDTO::class, 'name');
        $method= new ReflectionMethod(self::class, 'getNoSupport');
        $attr = $method->getAttributes()[0];

        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('Unsupported attribute type');
        DTOAttributesEnum::tryFromAttr($attr, 'any', $property, DTOTransformer::class);
    }

    public function testTransformDtoCollectionWithEmptyArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ValidDTO::fromArray([]);
    }
}