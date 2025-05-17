<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests;

use PHPUnit\Framework\TestCase;
use Ufo\DTO\DTOTransformer;
use Ufo\DTO\Tests\Fixtures\DTO\AliasDTO;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;
use Ufo\DTO\Tests\Fixtures\DTO\MemberWithFriendsDTO;
use Ufo\DTO\Tests\Fixtures\DTO\MemberWithFriendsWithKeysDTO;
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

        $this->assertInstanceOf(MemberWithFriendsDTO::class, $dto);;

        $this->assertSame('Alex', $dto->user->name);
        $this->assertSame('alex@site.com', $dto->user->email);
        $this->assertNotSame($initTime, $dto->user->currentTime);
        $this->assertGreaterThan($initTime, $dto->user->currentTime);

        $this->assertInstanceOf(UserDto::class, $dto->user);;
        $this->assertInstanceOf(UserDto::class, $dto->friends[0]);;
        $this->assertInstanceOf(UserDto::class, $dto->friends[1]);;
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
}
