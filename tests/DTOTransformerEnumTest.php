<?php

declare(strict_types = 1);

namespace Ufo\DTO\Tests;

use PHPUnit\Framework\TestCase;
use Ufo\DTO\DTOTransformer;
use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Exceptions\NotSupportDTOException;
use Ufo\DTO\Tests\Fixtures\DTO\DTOWithEnumAndDTOValue;
use Ufo\DTO\Tests\Fixtures\DTO\DTOWithEnumValue;
use Ufo\DTO\Tests\Fixtures\Enum\IntEnum;
use Ufo\DTO\Tests\Fixtures\Enum\StringEnum;

class DTOTransformerEnumTest extends TestCase
{
    /**
     * @return array<string, string>
     */
    private function namespaces(): array
    {
        return [
            DTOTransformer::DTO_NS_KEY => __NAMESPACE__ . '\Fixtures\DTO',
        ];
    }

    public function testFromArrayWithBackedEnums(): void
    {
        $dto = DTOTransformer::fromArray(DTOWithEnumValue::class, [
            'stringEnum' => 'b', // StringEnum::B
            'intEnum'    => 2,   // IntEnum::B
        ]);

        $this->assertInstanceOf(DTOWithEnumValue::class, $dto);
        $this->assertSame(StringEnum::B, $dto->stringEnum);
        $this->assertSame(IntEnum::B, $dto->intEnum);
    }

    public function testFromSmartArrayWithBackedEnums(): void
    {
        $smart = [
            '$className' => 'DTOWithEnumValue',
            'stringEnum' => 'a',
            'intEnum'    => 3,
        ];

        /** @var DTOWithEnumValue $dto */
        $dto = DTOTransformer::fromSmartArray($smart, namespaces: $this->namespaces());

        $this->assertInstanceOf(DTOWithEnumValue::class, $dto);
        $this->assertSame(StringEnum::A, $dto->stringEnum);
        $this->assertSame(IntEnum::C, $dto->intEnum);
    }

    public function testInvalidStringEnumValueThrows(): void
    {
        $this->expectException(BadParamException::class);

        DTOTransformer::fromArray(DTOWithEnumValue::class, [
            'stringEnum' => 'zzz',
            'intEnum'    => 1,
        ]);
    }

    public function testInvalidIntEnumValueThrows(): void
    {
        $this->expectException(BadParamException::class);

        DTOTransformer::fromArray(DTOWithEnumValue::class, [
            'stringEnum' => 'c',
            'intEnum'    => 999,
        ]);
    }

    public function testStringNumberForIntEnumBackedValueThrows(): void
    {
        $this->expectException(BadParamException::class);

        DTOTransformer::fromArray(DTOWithEnumValue::class, [
            'stringEnum' => 'a',
            'intEnum'    => 'df'
        ]);
    }

    public function testMissingClassNameInSmartArrayThrows(): void
    {
        $this->expectException(NotSupportDTOException::class);
        $this->expectExceptionMessage('Missing class name');

        DTOTransformer::fromSmartArray([
            'stringEnum' => 'a',
            'intEnum'    => 1,
        ], namespaces: $this->namespaces());
    }

    public function testUnknownClassNameInSmartArrayThrows(): void
    {
        $this->expectException(NotSupportDTOException::class);
        $this->expectExceptionMessage('Namespace not found for class');

        DTOTransformer::fromSmartArray([
            '$className' => 'DTOWithEnumValue',
            'stringEnum' => 'a',
            'intEnum'    => 1,
        ], namespaces: []);
    }

    public function testToArrayWithEnumsWithoutSmartFlag(): void
    {
        $dto = DTOTransformer::fromArray(DTOWithEnumValue::class, [
            'stringEnum' => 'c', // StringEnum::C
            'intEnum'    => 1,   // IntEnum::A
        ]);

        $arr = DTOTransformer::toArray($dto, asSmartArray: false);

        $this->assertSame([
            'stringEnum' => 'c',
            'intEnum'    => 1,
        ], $arr);
    }

    public function testToArrayWithEnumsSmartArray(): void
    {
        $dto = DTOTransformer::fromArray(DTOWithEnumValue::class, [
            'stringEnum' => 'a',
            'intEnum'    => 3,
        ]);

        $arr = DTOTransformer::toArray($dto);

        $this->assertArrayHasKey('stringEnum', $arr);
        $this->assertArrayHasKey('intEnum', $arr);
        $this->assertSame('a', $arr['stringEnum']);
        $this->assertSame(3, $arr['intEnum']);
    }

    public function testToArrayWithEnums(): void
    {
        $dto = DTOTransformer::fromArray(DTOWithEnumAndDTOValue::class, [
            'stringEnum' => 'a',
            'intEnum'    => 3,
            'onlyNameEnum' => 'A',
            'dummyDTO' => [
                'id' => 1,
                'name' => 'Test'
            ]
        ]);

        $arr = DTOTransformer::toArray($dto);

        $this->assertArrayHasKey('stringEnum', $arr);
        $this->assertArrayHasKey('intEnum', $arr);
        $this->assertSame('a', $arr['stringEnum']);
        $this->assertSame(3, $arr['intEnum']);
        $this->assertSame('A', $arr['onlyNameEnum']);
    }

    public function testFromArrayWithEnums(): void
    {
        $this->expectException(BadParamException::class);

        DTOTransformer::fromArray(DTOWithEnumAndDTOValue::class, [
            'stringEnum' => 'a',
            'intEnum'    => 3,
            'onlyNameEnum' => '1', // немає
            'dummyDTO' => [
                'id' => 1,
                'name' => 'Test'
            ]
        ]);
    }
}