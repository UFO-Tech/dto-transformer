<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests;

use PHPUnit\Framework\TestCase;
use Ufo\DTO\DTOTransformer;
use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Exceptions\NotSupportDTOException;
use Ufo\DTO\Tests\Fixtures\DTO\AliasDTO;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;
use Ufo\DTO\Tests\Fixtures\DTO\ItemDTO;
use Ufo\DTO\Tests\Fixtures\DTO\MemberWithFriendsDTO;
use Ufo\DTO\Tests\Fixtures\DTO\MemberWithFriendsWithKeysDTO;
use Ufo\DTO\Tests\Fixtures\DTO\ObjectWithArrayDTO;
use Ufo\DTO\Tests\Fixtures\DTO\ObjectWithUnionTypeDTO;
use Ufo\DTO\Tests\Fixtures\DTO\UnionWithScalarDTO;
use Ufo\DTO\Tests\Fixtures\DTO\UserDto;
use Ufo\DTO\Tests\Fixtures\DTO\WrapperDTO;
use Ufo\DTO\Tests\Fixtures\Enum\TestBackedEnum;
use Ufo\DTO\Tests\Fixtures\Enum\TestNonBackedEnum;

use function time;

final class DTOTransformerTest extends TestCase
{
    public function testTransformEnumWithValidBackedEnum(): void
    {
        $result = DTOTransformer::transformEnum(TestBackedEnum::class, 1);
        $this->assertSame(TestBackedEnum::VALUE_1, $result);
    }

    public function testTransformEnumWithInvalidBackedEnum(): void
    {
        $this->expectException(BadParamException::class);
        DTOTransformer::transformEnum(TestBackedEnum::class, 2);
    }

    public function testTransformEnumWithInvalidTypeBackedEnum(): void
    {
        $this->expectException(\TypeError::class);
        DTOTransformer::transformEnum(TestBackedEnum::class, 'sd');
    }

    public function testTransformEnumWithValidNonBackedEnum(): void
    {
        $result = DTOTransformer::transformEnum(TestNonBackedEnum::class, 'CASE_ONE');
        $this->assertSame(TestNonBackedEnum::CASE_ONE, $result);
    }

    public function testTransformEnumWithInvalidNonBackedEnumValue(): void
    {
        $result = DTOTransformer::transformEnum(TestNonBackedEnum::class, 'INVALID_CASE');
        $this->assertSame('INVALID_CASE', $result);
    }

    public function testIsSupportClass(): void
    {

        // Valid class check
        $this->assertTrue(DTOTransformer::isSupportClass(DummyDTO::class), 'Expected true for a valid class.');

        // Invalid/nonexistent class check
        $this->assertFalse(DTOTransformer::isSupportClass('NonExistentClass'), 'Expected true for a nonexistent class.');
    }

    public function testFromArrayAndToArray(): void
    {
        $input = ['id' => 1, 'name' => 'Test'];

        $dto = DTOTransformer::fromArray(DummyDTO::class, $input);

        $this->assertInstanceOf(DummyDTO::class, $dto);
        $this->assertSame(1, $dto->id);
        $this->assertSame('Test', $dto->name);

        $output = DTOTransformer::toArray($dto, asSmartArray: false);
        $this->assertSame($input, $output);
    }

    public function testAliasAttributeTransformsKeysCorrectly(): void
    {
        $rename = ['id' => 'external_id'];
        $input = ['external_id' => 99, 'name' => 'AliasTest'];
        $dto = DTOTransformer::fromArray(AliasDTO::class, $input, $rename);

        $this->assertInstanceOf(AliasDTO::class, $dto);;

        $this->assertSame(99, $dto->id);
        $this->assertSame('AliasTest', $dto->name);

        $output = DTOTransformer::toArray($dto, $rename, asSmartArray: false);
        $this->assertSame($input, $output);
    }

    public function testSkipAttributeCorrectly(): void
    {
        $initTime = 1746948360;
        $data = [
            'user' => [
                'name' => 'Alex',
                'email' => 'alex@site.com',
                'currentTime' => $initTime,
            ],
            'friends' => [
                [
                    'name' => 'Ivan',
                    'email' => 'ivan@site.com',
                    'currentTime' => $initTime,
                ],
                [
                    'name' => 'Peter',
                    'email' => 'peter@site.com',
                    'currentTime' => $initTime,
                ]
            ]
        ];
        /**
         * @var MemberWithFriendsDTO $dto
         */
        $dto = DTOTransformer::fromArray(MemberWithFriendsDTO::class, $data);

        $this->assertInstanceOf(MemberWithFriendsDTO::class, $dto);

        $this->assertSame('Alex', $dto->user->name);
        $this->assertSame('alex@site.com', $dto->user->email);
        $this->assertSame($initTime, $dto->user->currentTime);

        $this->assertInstanceOf(UserDto::class, $dto->user);
        $this->assertInstanceOf(UserDto::class, $dto->friends[0]);
        $this->assertInstanceOf(UserDto::class, $dto->friends[1]);
    }

    public function testArraysWithKeys(): void
    {
        $input = [
            'user' => [
                'id' => 1,
                'name' => 'Alex',
            ],
            'friends' => [
                'Friend1' => [
                    'id' => 2,
                    'name' => 'Friend1',
                ],
                [
                    'id' => 3,
                    'name' => 'Friend2',
                ],
            ]
        ];

       $obj = DTOTransformer::fromArray(MemberWithFriendsWithKeysDTO::class, $input);

       $array = DTOTransformer::toArray($obj, asSmartArray: false);
       $this->assertSame($input, $array);
    }

    public function testObjectWithArray(): void
    {
        $data = [
            'name' => 'Alex',
            'data' => [
                'foo',
                'bar',
                'baz'
            ]
        ];

        $obj = DTOTransformer::fromArray(ObjectWithArrayDTO::class, $data);

        $array = DTOTransformer::toArray($obj, asSmartArray: false);
        $this->assertSame($data, $array);
    }

    public function testObjectWithUnionType(): void
    {
        $data = [
            'name' => 'Alex',
            'data' => [
                'foo',
                'bar',
                'baz'
            ]
        ];

        $obj = DTOTransformer::fromArray(ObjectWithUnionTypeDTO::class, $data);

        $array = DTOTransformer::toArray($obj, asSmartArray: false);
        $this->assertSame($data, $array);
    }

    public function testUnionTypedPropertyResolution(): void
    {
        $dummyData = [
            'friend' => [
                'id' => 42,
                'name' => 'Dummy Name',
            ]
        ];

        $userData = [
            'friend' => [
                'name' => 'User Name',
                'email' => 'user@example.com',
                'currentTime' => time()
            ]
        ];

        $dtoFromDummy = DTOTransformer::fromArray(ItemDTO::class, $dummyData);
        $dtoFromUser = DTOTransformer::fromArray(ItemDTO::class, $userData);

        $this->assertInstanceOf(ItemDTO::class, $dtoFromDummy);
        $this->assertInstanceOf(DummyDTO::class, $dtoFromDummy->friend);
        $this->assertSame(42, $dtoFromDummy->friend->id);
        $this->assertSame('Dummy Name', $dtoFromDummy->friend->name);

        $this->assertInstanceOf(ItemDTO::class, $dtoFromUser);
        $this->assertInstanceOf(UserDto::class, $dtoFromUser->friend);
        $this->assertSame('User Name', $dtoFromUser->friend->name);
        $this->assertSame('user@example.com', $dtoFromUser->friend->email);
    }

    public function testValue1AcceptsUserDto(): void
    {
        $dto = DTOTransformer::fromArray(UnionWithScalarDTO::class, [
            'value1' => ['name' => 'Vasya', 'email' => 'vasya@site.com', 'currentTime' => time()],
            'value2' => [],
            'value3' => 1,
        ]);

        $this->assertInstanceOf(UserDto::class, $dto->value1);
        $this->assertSame('Vasya', $dto->value1->name);
        $this->assertIsArray($dto->value2);
        $this->assertSame(1, $dto->value3);
    }

    public function testValue1AcceptsString(): void
    {
        $dto = DTOTransformer::fromArray(UnionWithScalarDTO::class, [
            'value1' => 'just a string',
            'value2' => [],
            'value3' => 1,
        ]);

        $this->assertIsString($dto->value1);
        $this->assertSame('just a string', $dto->value1);
        $this->assertIsArray($dto->value2);
        $this->assertSame(1, $dto->value3);
    }

    public function testValue1IsRequired(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        DTOTransformer::fromArray(UnionWithScalarDTO::class, [
            'value2' => [],
            'value3' => 1,
        ]);
    }

    public function testValue2AcceptsDummyDTO(): void
    {
        $dto = DTOTransformer::fromArray(UnionWithScalarDTO::class, [
            'value1' => 'x',
            'value2' => ['id' => 5, 'name' => 'Test Dummy'],
            'value3' => 1,
        ]);

        $this->assertIsString($dto->value1);
        $this->assertInstanceOf(DummyDTO::class, $dto->value2);
        $this->assertSame(5, $dto->value2->id);
        $this->assertSame(1, $dto->value3);
    }

    public function testValue2AcceptsRawArray(): void
    {
        $dto = DTOTransformer::fromArray(UnionWithScalarDTO::class, [
            'value1' => 'x',
            'value2' => ['custom' => 'raw array'],
            'value3' => 1,
        ]);

        $this->assertIsString($dto->value1);
        $this->assertIsArray($dto->value2);
        $this->assertSame(['custom' => 'raw array'], $dto->value2);
        $this->assertSame(1, $dto->value3);
    }

    public function testValue2IsOptional(): void
    {
        $dto = DTOTransformer::fromArray(UnionWithScalarDTO::class, [
            'value1' => 'x',
            'value3' => 1,
        ]);

        $this->assertIsString($dto->value1);
        $this->assertNull($dto->value2);
        $this->assertSame(1, $dto->value3);
    }

    public function testValue3AcceptsUserDto(): void
    {
        $dto = DTOTransformer::fromArray(UnionWithScalarDTO::class, [
            'value1' => 'x',
            'value2' => [],
            'value3' => ['name' => 'Inna', 'email' => 'inna@site.com', 'currentTime' => time()],
        ]);

        $this->assertIsString($dto->value1);
        $this->assertIsArray($dto->value2);
        $this->assertInstanceOf(UserDto::class, $dto->value3);
        $this->assertSame('Inna', $dto->value3->name);
    }

    public function testValue3AcceptsNull(): void
    {
        $dto = DTOTransformer::fromArray(UnionWithScalarDTO::class, [
            'value1' => 'x',
            'value2' => [],
            'value3' => null,
        ]);

        $this->assertIsString($dto->value1);
        $this->assertIsArray($dto->value2);
        $this->assertNull($dto->value3);
    }

    public function testValue3AcceptsInt(): void
    {
        $dto = DTOTransformer::fromArray(UnionWithScalarDTO::class, [
            'value1' => 'x',
            'value2' => [],
            'value3' => 123,
        ]);

        $this->assertIsString($dto->value1);
        $this->assertIsArray($dto->value2);
        $this->assertSame(123, $dto->value3);
    }

    public function testAllValuesMustBePassedIfRequired(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        DTOTransformer::fromArray(UnionWithScalarDTO::class, [
            'value3' => 123,
        ]);
    }

    public function testInvalidStructureInUnionResultsInException(): void
    {
        $this->expectException(BadParamException::class);
        $this->expectExceptionMessageMatches('/Cannot assign array to property .*UnionWithScalarDTO/');

        DTOTransformer::fromArray(UnionWithScalarDTO::class, [
            'value1' => ['something' => 'unexpected'],
            'value2' => [],
            'value3' => 1,
        ]);
    }

    public function testIncompleteObjectInUnionThrowsException(): void
    {
        $this->expectException(BadParamException::class);

        DTOTransformer::fromArray(UnionWithScalarDTO::class, [
            'value1' => ['name' => 'Only name'],
            'value2' => [],
            'value3' => 1,
        ]);
    }

    public function testMatchingClassFailsDuringConstruction(): void
    {
        $this->expectException(BadParamException::class);
        $this->expectExceptionMessageMatches('/Cannot assign array to/');

        DTOTransformer::fromArray(UnionWithScalarDTO::class, [
            'value1' => ['fail' => true],
            'value2' => [],
            'value3' => 1,
        ]);
    }

    public function testUnionWithoutNullRejectsNull(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        DTOTransformer::fromArray(UnionWithScalarDTO::class, [
            'value1' => null,
            'value2' => [],
            'value3' => 1,
        ]);
    }

    public function testInvalidFriendDataThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Missing required key for property: 'id'");

        $input = [
            'user' => [
                'id' => 1,
                'name' => 'Alex',
            ],
            'friends' => [
                [
                    // missing 'id'
                    'name' => 'BadFriend',
                ]
            ]
        ];

        DTOTransformer::fromArray(MemberWithFriendsWithKeysDTO::class, $input);
    }

    public function testItTransformsCollectionOfDTOsWithUnionArrayType(): void
    {
        $input = [
            'items' => [
                ['id' => 1, 'name' => 'Alpha'],
                ['id' => 2, 'name' => 'Beta'],
            ],
        ];

        /** @var WrapperDTO $dto */
        $dto = DTOTransformer::fromArray(WrapperDTO::class, $input);

        // Перевірка типу
        $this->assertInstanceOf(WrapperDTO::class, $dto);
        $this->assertIsArray($dto->items);
        $this->assertCount(2, $dto->items);

        foreach ($dto->items as $item) {
            $this->assertInstanceOf(DummyDTO::class, $item);
        }

        $this->assertEquals(1, $dto->items[0]->id);
        $this->assertEquals('Alpha', $dto->items[0]->name);
        $this->assertEquals(2, $dto->items[1]->id);
        $this->assertEquals('Beta', $dto->items[1]->name);
    }

    public function testItFailsWhenNonDTOValuesProvided(): void
    {
        $input = [
            'items' => [1, 2, 3],
        ];

        $this->expectException(BadParamException::class);
        DTOTransformer::fromArray(WrapperDTO::class, $input);
    }

    public function testItFailsWhenSingleDTOProvidedInsteadOfCollection(): void
    {
        $input = [
            'items' => ['id' => 1, 'name' => 'Alpha'],
        ];

        $this->expectException(BadParamException::class);
        DTOTransformer::fromArray(WrapperDTO::class, $input);
    }

    public function testFromArrayWithMultipleClassNames(): void
    {
        $dataUser = ['name' => 'name1', 'email' => 'email@email.com', 'currentTime' => time()];
        $dataDummy = ['id' => 1, 'name' => 'name1'];
        $classFQCN = 'UnsupportedClass|' . UserDto::class . '|' . DummyDTO::class;

        $user = DTOTransformer::fromArray($classFQCN, $dataUser);

        $this->assertInstanceOf(UserDto::class, $user);
        $this->assertEquals($dataUser['name'], $user->name);
        $this->assertEquals($dataUser['email'], $user->email);

        $dummy = DTOTransformer::fromArray($classFQCN, $dataDummy);

        $this->assertInstanceOf(DummyDTO::class, $dummy);
        $this->assertEquals($dataDummy['id'], $dummy->id);
        $this->assertEquals($dataDummy['name'], $dummy->name);
    }

    public function testFromArrayWithMultipleClassNamesWithDefaultNamespace(): void
    {
        $data = ['name' => 'name1', 'email' => 'email@email.com', 'currentTime' => time()];
        $classFQCN = 'UnsupportedClass|' . UserDto::class . '|' . DummyDTO::class;

        $result = DTOTransformer::fromArray($classFQCN, $data, namespaces: [
            DTOTransformer::DTO_NS_KEY => 'Ufo\DTO\Tests',
        ]);

        $this->assertInstanceOf(UserDto::class, $result);
        $this->assertEquals($data['name'], $result->name);
        $this->assertEquals($data['email'], $result->email);
    }

    public function testBadParamFromArrayWithMultipleClass(): void
    {
        $this->expectException(BadParamException::class);

        $data = ['id' => "dasdds", 'name' => 'email@email.com'];
        $classFQCN = 'UnsupportedClass|' . 'Fixtures\DTO\UserDto' . '|' . DummyDTO::class;

        try {
            DTOTransformer::fromArray($classFQCN, $data, namespaces: [
                DTOTransformer::DTO_NS_KEY => 'Ufo\DTO\Tests',
            ]);
        } catch (BadParamException $e) {
            throw $e;
        }
    }

    public function testNotSupportFromArrayWithMultipleClass(): void
    {
        $this->expectException(BadParamException::class);

        $data = ['name' => 1, 'email' => 'email@email.com'];
        $classFQCN = 'UnsupportedClass|Fixtures\DTO\UserDto';

        DTOTransformer::fromArray($classFQCN, $data, namespaces: []);
    }
}
