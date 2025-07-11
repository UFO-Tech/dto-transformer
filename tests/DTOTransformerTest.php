<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests;

use PHPUnit\Framework\TestCase;
use Ufo\DTO\DTOTransformer;
use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Tests\Fixtures\DTO\AliasDTO;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;
use Ufo\DTO\Tests\Fixtures\DTO\ItemDTO;
use Ufo\DTO\Tests\Fixtures\DTO\MemberWithFriendsDTO;
use Ufo\DTO\Tests\Fixtures\DTO\MemberWithFriendsWithKeysDTO;
use Ufo\DTO\Tests\Fixtures\DTO\UnionWithScalarDTO;
use Ufo\DTO\Tests\Fixtures\DTO\UserDto;

final class DTOTransformerTest extends TestCase
{
    public function testFromArrayAndToArray(): void
    {
        $input = ['id' => 1, 'name' => 'Test'];

        $dto = DTOTransformer::fromArray(DummyDTO::class, $input);

        $this->assertInstanceOf(DummyDTO::class, $dto);
        $this->assertSame(1, $dto->id);
        $this->assertSame('Test', $dto->name);

        $output = DTOTransformer::toArray($dto);
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

        $output = DTOTransformer::toArray($dto, $rename);
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
        $this->assertNotSame($initTime, $dto->user->currentTime);
        $this->assertGreaterThan($initTime, $dto->user->currentTime);

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

       $array = DTOTransformer::toArray($obj);
       $this->assertSame($input, $array);;
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
            'value1' => ['name' => 'Vasya', 'email' => 'vasya@site.com'],
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
            'value3' => ['name' => 'Inna', 'email' => 'inna@site.com'],
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
        $this->expectExceptionMessage("Missing required key for constructor param: 'id'");

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
}
