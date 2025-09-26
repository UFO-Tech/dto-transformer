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

    public function testFromArrayWithSingleSupportedClass(): void
    {
        $data = ['param' => 'testValue'];
        $result = $this->createMockBaseDTOFromArrayTransformer()::fromArray(UserDto::class, $data);

        $this->assertIsObject($result);
        $this->assertEquals('testValue', $result->param);
    }

    public function testFromArrayWithMultipleSupportedClasses(): void
    {
        $data = ['username' => 'exampleUser'];
        $classes = 'DummyDTO|' . UserDto::class;
        $result = $this->createMockBaseDTOFromArrayTransformer()::fromArray($classes, $data);

        $this->assertIsObject($result);
        $this->assertEquals('exampleUser', $result->username);
    }

    public function testFromArrayWithUnmatchedClasses(): void
    {
        $this->expectException(NotSupportDTOException::class);
        $this->createMockBaseDTOFromArrayTransformer()::fromArray('InvalidClass', []);
    }

    public function testFromArrayWithInvalidData(): void
    {
        $this->expectException(BadParamException::class);
        $data = ['error' => true];
        $this->createMockBaseDTOFromArrayTransformer()::fromArray('SupportedClass', $data);
    }

    public function testFromArrayCallsTransformFromArrayFallback(): void
    {
        $data = ['email' => 'email@email.email', 'name' => 'name'];
        $result = $this->createMockBaseDTOFromArrayTransformer()::fromArray(
            UserDto::class,
            $data
        );

        $this->assertIsObject($result);
        $this->assertSame($data['email'], $result->email);
        $this->assertSame($data['name'], $result->name);
    }

    public function testFromArrayWithClassErrors(): void
    {
        $this->expectException(BadParamException::class);

        $data = ['invalid' => 'data'];
        $classes = 'DummyDTO|InvalidClass';
        $this->createMockBaseDTOFromArrayTransformer()::fromArray($classes, $data);
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

    public function testFromArrayFallbackToTransformFromArray(): void
    {
        $this->expectException(NotSupportDTOException::class);
        $data = ['keyX' => 'valueX', 'keyY' => 'valueY'];
        $this->createMockBaseDTOFromArrayTransformer()::fromArray('SupportedClass', $data);
    }

    public function testFromArrayWithMultipleClassNames(): void
    {
        $data = ['name' => 'name1', 'email' => 'email@email.com'];
        $classFQCN = 'UnsupportedClass|' . UserDto::class;

        $result = $this->createMockBaseDTOFromArrayTransformer()::fromArray($classFQCN, $data);

        $this->assertIsObject($result);
        $this->assertEquals('name1', $result->name);
        $this->assertEquals('email@email.com', $result->email);
    }

    public function testFromArrayWithInvalidClassNames(): void
    {
        $this->expectException(BadParamException::class);

        $this->createMockBaseDTOFromArrayTransformer()::fromArray('UnsupportedClass|InvalidClass', []);
    }

    public function testFromArrayThrowsBadParamException(): void
    {
        $this->expectException(BadParamException::class);

        $data = ['error' => 'invalidValue'];
        $this->createMockBaseDTOFromArrayTransformer()::fromArray('SupportedClass', $data);
    }
}