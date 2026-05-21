<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Hydrator;

use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Interfaces\DTOFromArrayTransformerInterface;
use Ufo\DTO\Transformer\Hydrator\DtoHydrator;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;
use Ufo\DTO\Tests\Fixtures\DTO\UserDto;
use Ufo\DTO\Tests\Support\TransformerFactory;

final class DtoHydratorTest extends ParamHydratorTestCase
{
    public function testSupportsClassSchema(): void
    {
        $this->assertTrue((new DtoHydrator($this->defaultTransformer()))->supports(self::dummySchema()));
    }

    public function testResolvesDtoFromArray(): void
    {
        $result = (new DtoHydrator(defaultDTOTransformer: $this->defaultTransformer()))->resolve(
            self::dummySchema(),
            self::DUMMY_DATA,
            $this->context(),
        );

        $this->assertInstanceOf(DummyDTO::class, $result);
        $this->assertSame('Dummy', $result->name);
    }

    public function testKeepsExistingObject(): void
    {
        $dummy = new DummyDTO(2, 'Existing');

        $result = (new DtoHydrator($this->defaultTransformer()))->resolve(
            self::dummySchema(),
            $dummy,
            $this->context(),
        );

        $this->assertSame($dummy, $result);
    }

    public function testResolvesSmartClassNameWhenAllowed(): void
    {
        $result = (new DtoHydrator($this->defaultTransformer()))->resolve(
            self::dummySchema(),
            [
                '$className' => UserDto::class,
                'name' => 'Smart User',
                'email' => 'smart@example.com',
                'currentTime' => 123,
            ],
            $this->context(),
        );

        $this->assertInstanceOf(UserDto::class, $result);
        $this->assertSame('Smart User', $result->name);
    }

    public function testUsesCustomFromArrayTransformerBeforeDefaultBuilder(): void
    {
        $result = (new DtoHydrator($this->defaultTransformer(), [
            new class implements DTOFromArrayTransformerInterface {
                public function transformFromArray(
                    string $classFQCN,
                    array $data,
                    array $renameKey = [],
                    array $namespaces = [],
                    array $context = [],
                ): object {
                    return new DummyDTO($data['id'], 'Custom');
                }

                public function support(string $classFQCN): bool
                {
                    return $classFQCN === DummyDTO::class;
                }
            },
        ]))->resolve(
            self::dummySchema(),
            self::DUMMY_DATA,
            $this->context(),
        );

        $this->assertInstanceOf(DummyDTO::class, $result);
        $this->assertSame('Custom', $result->name);
    }

    public function testThrowsForNonArrayValue(): void
    {
        $this->expectException(BadParamException::class);

        (new DtoHydrator($this->defaultTransformer()))->resolve(
            self::dummySchema(),
            'not-array',
            $this->context(),
        );
    }

    private function defaultTransformer(): DTOFromArrayTransformerInterface
    {
        return TransformerFactory::fromArrayTransformer();
    }
}
