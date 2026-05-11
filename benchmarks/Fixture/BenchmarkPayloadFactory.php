<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks\Fixture;

use Ufo\DTO\Attributes\AttrDTO;
use Ufo\DTO\DTOTransformer;
use Ufo\DTO\Tests\Fixtures\DTO\DTOWithEnumAndDTOValue;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;
use Ufo\DTO\Tests\Fixtures\DTO\MemberWithFriendsDTO;
use Ufo\DTO\Tests\Fixtures\DTO\ObjectWithArrayDTO;
use Ufo\DTO\Tests\Fixtures\DTO\UserDto;

final class BenchmarkPayloadFactory
{
    public static function fixtureClass(): string
    {
        return new class {
            #[AttrDTO(DummyDTO::class, context: [AttrDTO::C_COLLECTION => true])]
            public array $dummyItems;

            #[AttrDTO(UserDto::class, context: [AttrDTO::C_COLLECTION => true])]
            public array $users;

            #[AttrDTO(MemberWithFriendsDTO::class, context: [AttrDTO::C_COLLECTION => true])]
            public array $members;

            #[AttrDTO(DTOWithEnumAndDTOValue::class, context: [AttrDTO::C_COLLECTION => true])]
            public array $enumBackedItems;

            #[AttrDTO(ObjectWithArrayDTO::class, context: [AttrDTO::C_COLLECTION => true])]
            public array $arrayObjects;
        }::class;
    }

    public static function namespaces(): array
    {
        return [
            'DTOWithEnumAndDTOValue' => DTOWithEnumAndDTOValue::class,
            'DummyDTO' => DummyDTO::class,
            'MemberWithFriendsDTO' => MemberWithFriendsDTO::class,
            'ObjectWithArrayDTO' => ObjectWithArrayDTO::class,
            'UserDto' => UserDto::class,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function rootArrayPayloadCollection(
        int $rootCount,
        int $nestedSize,
    ): array {
        $items = [];

        for ($i = 0; $i < $rootCount; $i++) {
            $items[] = self::singleRootPayload($nestedSize);
        }

        return $items;
    }

    /**
     * @return object[]
     */
    public static function rootDtoPayloadCollection(
        int $rootCount,
        int $nestedSize,
    ): array {
        $items = [];
        $fixtureClass = self::fixtureClass();
        $namespaces = self::namespaces();

        for ($i = 0; $i < $rootCount; $i++) {
            $items[] = DTOTransformer::fromArray(
                $fixtureClass,
                self::singleRootPayload($nestedSize),
                namespaces: $namespaces,
            );
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private static function singleRootPayload(int $nestedSize): array
    {
        return [
            'dummyItems' => self::generateDummyItems($nestedSize),
            'users' => self::generateUsers($nestedSize),
            'members' => self::generateMembers($nestedSize),
            'enumBackedItems' => self::generateEnumBackedItems($nestedSize),
            'arrayObjects' => self::generateArrayObjects($nestedSize),
        ];
    }

    private static function generateDummyItems(int $count): array
    {
        $items = [];

        for ($i = 0; $i < $count; $i++) {
            $items[] = [
                'id' => $i,
                'name' => 'Dummy ' . $i,
            ];
        }

        return $items;
    }

    private static function generateUsers(int $count): array
    {
        $items = [];

        for ($i = 0; $i < $count; $i++) {
            $items[] = [
                'name' => 'User ' . $i,
                'email' => 'user-' . $i . '@example.com',
                'currentTime' => 1_746_948_360 + $i,
            ];
        }

        return $items;
    }

    private static function generateMembers(int $count): array
    {
        $items = [];

        for ($i = 0; $i < $count; $i++) {
            $items[] = [
                'user' => [
                    'name' => 'Member ' . $i,
                    'email' => 'member-' . $i . '@example.com',
                    'currentTime' => 1_746_948_360 + $i,
                ],
                'friends' => [
                    [
                        'name' => 'Friend ' . $i . '-1',
                        'email' => 'friend-' . $i . '-1@example.com',
                        'currentTime' => 1_746_948_360 + $i,
                    ],
                    [
                        'name' => 'Friend ' . $i . '-2',
                        'email' => 'friend-' . $i . '-2@example.com',
                        'currentTime' => 1_746_948_361 + $i,
                    ],
                    [
                        'name' => 'Friend ' . $i . '-3',
                        'email' => 'friend-' . $i . '-3@example.com',
                        'currentTime' => 1_746_948_362 + $i,
                    ],
                ],
            ];
        }

        return $items;
    }

    private static function generateEnumBackedItems(int $count): array
    {
        $items = [];

        for ($i = 0; $i < $count; $i++) {
            $items[] = [
                'stringEnum' => 'a',
                'intEnum' => 1,
                'onlyNameEnum' => 'A',
                'dummyDTO' => [
                    'id' => $i,
                    'name' => 'Enum Dummy ' . $i,
                ],
            ];
        }

        return $items;
    }

    private static function generateArrayObjects(int $count): array
    {
        $items = [];

        for ($i = 0; $i < $count; $i++) {
            $items[] = [
                'name' => 'array-object-' . $i,
                'data' => [
                    'active' => $i % 2 === 0,
                    'rank' => $i,
                    'tags' => [
                        'tag-' . $i,
                        'tag-' . ($i + 1),
                    ],
                ],
            ];
        }

        return $items;
    }
}
