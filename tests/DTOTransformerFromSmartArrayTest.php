<?php
declare(strict_types=1);

namespace Ufo\DTO\Tests;

use PHPUnit\Framework\TestCase;
use Ufo\DTO\DTOTransformer;
use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Exceptions\NotSupportDTOException;
use Ufo\DTO\Tests\Fixtures\DTO\AliasDTO;
use Ufo\DTO\Tests\Fixtures\DTO\DocblockDTO;
use Ufo\DTO\Tests\Fixtures\DTO\DTOWithEnumValue;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO2;
use Ufo\DTO\Tests\Fixtures\DTO\ItemDTO;
use Ufo\DTO\Tests\Fixtures\DTO\ObjectWithArrayDTO;
use Ufo\DTO\Tests\Fixtures\DTO\UserDto;

final class DTOTransformerFromSmartArrayTest extends TestCase
{
    /**
     * @return array<string, class-string>
     */
    private function namespaces(): array
    {
        return [
            DTOTransformer::DTO_NS_KEY => __NAMESPACE__ . '\Fixtures\DTO'
        ];
    }

    public function testToArrayAddsShortClassNameKey(): void
    {
        $dto = new DummyDTO(5, 'john');

        $arr = DTOTransformer::toArray($dto, asSmartArray: true);

        $this->assertSame(5, $arr['id']);
        $this->assertSame('john', $arr['name']);

        $this->assertArrayHasKey('$className', $arr);
        $this->assertSame('DummyDTO', $arr['$className']);
    }

    public function testRoundTripDummyDto(): void
    {
        $src = new DummyDTO(7, 'alice');

        $smart = DTOTransformer::toArray($src, asSmartArray: true);
        $restored = DTOTransformer::fromSmartArray($smart, namespaces: $this->namespaces());

        $this->assertInstanceOf(DummyDTO::class, $restored);
        $this->assertSame(7, $restored->id);
        $this->assertSame('alice', $restored->name);
    }

    public function testRoundTripAliasDto(): void
    {
        $src = new AliasDTO(id: 10, name: 'qwe');

        $smart = DTOTransformer::toArray($src, asSmartArray: true);
        $restored = DTOTransformer::fromSmartArray($smart, namespaces: $this->namespaces());

        $this->assertInstanceOf(AliasDTO::class, $restored);
        $this->assertSame(10, $restored->id);
        $this->assertSame('qwe', $restored->name);
    }

    public function testRoundTripObjectWithArrayDto(): void
    {
        $src = new ObjectWithArrayDTO(name: 'bucket', data: ['a' => 1, 'b' => ['x' => 2]]);

        $smart = DTOTransformer::toArray($src, asSmartArray: true);
        $restored = DTOTransformer::fromSmartArray($smart, namespaces: $this->namespaces());

        $this->assertInstanceOf(ObjectWithArrayDTO::class, $restored);
        $this->assertSame('bucket', $restored->name);
        $this->assertSame(['a' => 1, 'b' => ['x' => 2]], $restored->data);
    }

    public function testFromSmartArrayWithRenameKey(): void
    {
        $data = [
            '$className' => 'AliasDTO',
            'alias_id'   => 42,
            'alias_name' => 'zxc',
        ];

        $renameKey = [
            // dtoKey => dataKey
            'id'   => 'alias_id',
            'name' => 'alias_name',
        ];

        /** @var AliasDTO $dto */
        $dto = DTOTransformer::fromSmartArray($data, renameKey: $renameKey, namespaces: $this->namespaces());

        $this->assertInstanceOf(AliasDTO::class, $dto);
        $this->assertSame(42, $dto->id);
        $this->assertSame('zxc', $dto->name);
    }

    public function testThrowsWhenClassNameKeyMissing(): void
    {
        $this->expectException(NotSupportDTOException::class);
        $this->expectExceptionMessage('Missing class name');

        DTOTransformer::fromSmartArray(['id' => 1, 'name' => 'nope'], namespaces: $this->namespaces());
    }

    public function testThrowsWhenNamespaceMappingMissing(): void
    {
        $this->expectException(NotSupportDTOException::class);
        $this->expectExceptionMessage('Namespace not found for class');

        $data = [
            '$className' => 'DummyDTO',
            'id'   => 1,
            'name' => 'pete',
        ];

        DTOTransformer::fromSmartArray($data, namespaces: []);
    }

    public function testFromSmartArrayItemDtoWhenFriendHasExplicitClassNameUserDto(): void
    {
        $smart = [
            '$className' => 'ItemDTO',
            'friend' => [
                '$className' => 'UserDto',
                'email' => 'email',
                'name' => 'explicit-user',
            ],
        ];

        /** @var ItemDTO $restored */
        $restored = DTOTransformer::fromSmartArray($smart, namespaces: $this->namespaces());

        $this->assertInstanceOf(ItemDTO::class, $restored);
        $this->assertInstanceOf(UserDto::class, $restored->friend);
        $this->assertSame('email', $restored->friend->email);
        $this->assertSame('explicit-user', $restored->friend->name);
    }

    public function testFromSmartArrayItemDtoNotTransform(): void
    {
        $this->expectException(BadParamException::class);

        $smart = [
            '$className' => 'ItemDTO',
            'friend' => [
                '$className' => 'UserDto',
                'id' => 'id',
                'name' => 'explicit-user',
            ],
        ];

        /** @var ItemDTO $restored */
        $restored = DTOTransformer::fromSmartArray($smart, namespaces: $this->namespaces());
    }

    public function testFromSmartArrayItemDtoWhenFriendHasExplicitClassNameDummyDto(): void
    {
        $smart = [
            '$className' => 'ItemDTO',
            'friend' => [
                '$className' => 'DummyDTO',
                'id'   => 9,
                'name' => 'explicit-dummy',
            ],
        ];

        /** @var ItemDTO $restored */
        $restored = DTOTransformer::fromSmartArray($smart, namespaces: $this->namespaces());

        $this->assertInstanceOf(ItemDTO::class, $restored);
        $this->assertInstanceOf(DummyDTO::class, $restored->friend);
        $this->assertSame(9, $restored->friend->id);
        $this->assertSame('explicit-dummy', $restored->friend->name);
    }

    public function testFromSmartArrayItemDtoWhenFriendExplicitClassNameMismatchThrows(): void
    {
        $this->expectException(BadParamException::class);

        $smart = [
            '$className' => 'ItemDTO',
            'friend' => [
                '$className' => 'UserDto',
                'unknown' => 'field',
            ],
        ];

        DTOTransformer::fromSmartArray($smart, namespaces: $this->namespaces());
    }

    public function testDepthWithSmartObject(): void
    {
        $smart = [
            'friend' => [
                '$className' => 'UserDto',
                'email' => 'email',
                'name' => 'explicit-user',
            ],
        ];

        /** @var ItemDTO $restored */
        $restored = DTOTransformer::fromArray(ItemDTO::class, $smart, namespaces: $this->namespaces());

        $this->assertInstanceOf(ItemDTO::class, $restored);
        $this->assertInstanceOf(UserDto::class, $restored->friend);
        $this->assertSame('explicit-user', $restored->friend->name);
        $this->assertSame('email', $restored->friend->email);
    }

    public function testSmartObject(): void
    {
        $smart = [
            '$className' => 'ItemDTO',
            'friend' => [
                'email' => 'email',
                'name' => 'explicit-user',
            ],
        ];

        /** @var ItemDTO $restored */
        $restored = DTOTransformer::fromArray(ItemDTO::class, $smart, namespaces: $this->namespaces());

        $this->assertInstanceOf(ItemDTO::class, $restored);
        $this->assertInstanceOf(UserDto::class, $restored->friend);
        $this->assertSame('explicit-user', $restored->friend->name);
        $this->assertSame('email', $restored->friend->email);
    }

    public function testSmartObjectWithoutNamespaces(): void
    {
        $smart = [
            'friend' => [
                '$className' => 'DummyDTO2',
                'id' => 123,
                'name' => 'explicit-user',
            ],
        ];

        $smart2 = [
            'friend' => [
                '$className' => 'DummyDTO',
                'id' => 123,
                'name' => 'explicit-user',
            ],
        ];

        /** @var ItemDTO $restored */
        $restored = DTOTransformer::fromArray(ItemDTO::class, $smart);
        $restored2= DTOTransformer::fromArray(ItemDTO::class, $smart2);

        $this->assertInstanceOf(ItemDTO::class, $restored);
        $this->assertInstanceOf(DummyDTO2::class, $restored->friend);
        $this->assertInstanceOf(DummyDTO::class, $restored2->friend);
    }

    public function testDTOWithDocblock(): void
    {
        $dto = DTOTransformer::fromArray(DocblockDTO::class, [
            'name' => 'explicit-user',
            'collection' => [
                [
                    'id' => 123,
                    'name' => 'explicit-user',
                ]
            ]
        ]);

        $dto2 = DTOTransformer::fromArray(DocblockDTO::class, [
            'name' => 'explicit-user',
            'collection' => [
                [
                    'email' => 'email',
                    'name' => 'explicit-user',
                ]
            ]
        ]);

        $dto3 = DTOTransformer::fromArray(DocblockDTO::class, [
            'name' => 'explicit-user',
            'collection' => [
                [
                    'id' => 123,
                    'name' => 'explicit-user',
                ],
                [
                    'email' => 'email',
                    'name' => 'explicit-user',
                ],
                [
                    '$className' => 'DTOWithEnumValue',
                    'stringEnum' => 'a',
                    'intEnum' => 1,
                ]
            ]
        ]);

        $this->assertInstanceOf(DocblockDTO::class, $dto);
        $this->assertInstanceOf(DummyDTO::class, $dto->collection[0]);
        $this->assertInstanceOf(DocblockDTO::class, $dto2);
        $this->assertInstanceOf(UserDto::class, $dto2->collection[0]);
        $this->assertInstanceOf(DocblockDTO::class, $dto3);
        $this->assertInstanceOf(UserDto::class, $dto3->collection[1]);
        $this->assertInstanceOf(DummyDTO::class, $dto3->collection[0]);
        $this->assertInstanceOf(DTOWithEnumValue::class, $dto3->collection[2]);
    }
}