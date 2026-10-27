<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Hydrator;

use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Transformer\Context\TransformationContext;
use Ufo\DTO\Transformer\DTOFromArrayTransformerInterface;
use Ufo\DTO\Transformer\DTOFromContextTransformerInterface;
use Ufo\DTO\Transformer\Hydrator\DTOHydrator;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;
use Ufo\DTO\Tests\Fixtures\DTO\UserDto;
use Ufo\DTO\Tests\Support\TransformerFactory;

final class DTOHydratorTest extends HydratorTestCase
{
    public function testSupportsClassSchema(): void
    {
        $this->assertTrue((new DTOHydrator($this->defaultTransformer()))->supports(self::dummySchema()));
    }

    public function testResolvesDtoFromArray(): void
    {
        $result = (new DTOHydrator(defaultDTOTransformer: $this->defaultTransformer()))->resolve(
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

        $result = (new DTOHydrator($this->defaultTransformer()))->resolve(
            self::dummySchema(),
            $dummy,
            $this->context(),
        );

        $this->assertSame($dummy, $result);
    }

    public function testResolvesSmartClassNameWhenAllowed(): void
    {
        $result = (new DTOHydrator($this->defaultTransformer()))->resolve(
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
        $result = (new DTOHydrator($this->defaultTransformer(), [
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

    public function testPassesContextObjectToContextAwareTransformer(): void
    {
        $context = new TransformationContext(['Dto' => 'App\\Dto'], true, ['custom' => 'value']);
        $transformer = new class implements DTOFromArrayTransformerInterface, DTOFromContextTransformerInterface {
            public ?TransformationContext $received = null;

            public function transformFromArray(
                string $classFQCN,
                array $data,
                array $renameKey = [],
                array $namespaces = [],
                array $context = [],
            ): object {
                throw new \LogicException('Array path must not be used');
            }

            public function transformFromContext(
                string $classFQCN,
                array $data,
                TransformationContext $context,
                array $renameKey = [],
            ): object {
                $this->received = $context;

                return new DummyDTO($data['id'], $data['name']);
            }

            public function support(string $classFQCN): bool
            {
                return true;
            }
        };

        (new DTOHydrator($transformer))->resolve(self::dummySchema(), self::DUMMY_DATA, $context);

        $this->assertSame($context, $transformer->received);
    }

    public function testThrowsForNonArrayValue(): void
    {
        $this->expectException(BadParamException::class);

        (new DTOHydrator($this->defaultTransformer()))->resolve(
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
