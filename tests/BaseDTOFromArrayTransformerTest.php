<?php

declare(strict_types = 1);

namespace Ufo\DTO\Tests;

use PHPUnit\Framework\TestCase;
use Ufo\DTO\BaseDTOFromArrayTransformer;
use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Exceptions\NotSupportDTOException;
use Ufo\DTO\Interfaces\IDTOFromArrayTransformer;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;
use Ufo\DTO\Tests\Fixtures\DTO\UserDto;

class BaseDTOFromArrayTransformerTest extends TestCase
{
    protected function createMockBaseDTOFromArrayTransformer(): IDTOFromArrayTransformer
    {
        return new class extends BaseDTOFromArrayTransformer {
            public static function isSupportClass(string $classFQCN): bool
            {
                return (bool) in_array($classFQCN, ['SupportedClass', UserDto::class]);
            }

            protected static function transformFromArray(string $classFQCN, array $data, array $renameKey = [], array $namespaces = []): object
            {
                return (object) $data;
            }

            public static function fromSmartArray(array $data, array $renameKey = [], array $namespaces = []): object
            {
                if (isset($data['error'])) {
                    throw new NotSupportDTOException("Smart array transformation not supported.");
                }
                return (object) $data;
            }
        };
    }

    public function testFromArrayWithSupportedClass(): void
    {
        $data = ['key1' => 'value1', 'key2' => 'value2'];
        $result = $this->createMockBaseDTOFromArrayTransformer()::fromArray('SupportedClass', $data);

        $this->assertIsObject($result);
        $this->assertEquals('value1', $result->key1);
        $this->assertEquals('value2', $result->key2);
    }

    public function testFromArrayWithUnsupportedClass(): void
    {
        $this->expectException(NotSupportDTOException::class);

        $this->createMockBaseDTOFromArrayTransformer()::fromArray('UnsupportedClass', []);
    }

    public function testFromArrayThrowsNotSupportDTOException(): void
    {
        $this->expectException(NotSupportDTOException::class);

        $data = ['error' => true];
        $this->createMockBaseDTOFromArrayTransformer()::fromArray(DummyDTO::class, $data);
    }

    public function testFromArrayWithSmartArraySupport(): void
    {
        $data = ['keyA' => 'valueA', 'keyB' => 'valueB'];
        $result = $this->createMockBaseDTOFromArrayTransformer()::fromArray('SupportedClass', $data);

        $this->assertIsObject($result);
        $this->assertEquals('valueA', $result->keyA);
        $this->assertEquals('valueB', $result->keyB);
    }

    public function testFromArrayFallbackToTransformFromArray(): void
    {
        $data = ['keyX' => 'valueX', 'keyY' => 'valueY'];
        $result = $this->createMockBaseDTOFromArrayTransformer()::fromArray('SupportedClass', $data);

        $this->assertIsObject($result);
        $this->assertEquals('valueX', $result->keyX);
        $this->assertEquals('valueY', $result->keyY);
    }
}