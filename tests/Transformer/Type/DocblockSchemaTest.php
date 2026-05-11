<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Type;

use ReflectionMethod;
use ReflectionProperty;
use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;

final class DocblockSchemaTest extends ReflectionTypeSchemaResolverTestCase
{
    public function testDocblockSchemaBuildsNestedCollection(): void
    {
        $schema = $this->typeSchemaResolver()->docTypeSchema(
            new ReflectionProperty($this->nestedFixture(), 'items'),
        );
        $listSchema = $schema[TypeHintResolver::ADDITIONAL_PROPERTIES];

        $this->assertSame(TypeHintResolver::ARRAY->value, $listSchema[TypeHintResolver::TYPE]);
        $this->assertObjectSchema(DummyDTO::class, $listSchema[TypeHintResolver::ITEMS]);
    }

    public function testDocblockSchemaReadsMatchingConstructorParameter(): void
    {
        $constructor = new ReflectionMethod($this->constructorFixture(), '__construct');

        $schema = $this->typeSchemaResolver()->docTypeSchema($constructor->getParameters()[1]);

        $this->assertSame(TypeHintResolver::ARRAY->value, $schema[TypeHintResolver::TYPE]);
        $this->assertObjectSchema(DummyDTO::class, $schema[TypeHintResolver::ITEMS]);
    }

    public function testDocblockSchemaWinsWhenNativeTypeIsMixed(): void
    {
        $resolvedSchema = $this->resolvedSchemaFor($this->mismatchFixture(), 'mixedNativeDocblockDummyList');

        $this->assertTrue($resolvedSchema->fromDocblock);
        $this->assertFalse($resolvedSchema->strict);
        $this->assertObjectSchema(DummyDTO::class, $resolvedSchema->schema[TypeHintResolver::ITEMS]);
    }

    public function testDocblockSchemaWinsWhenNativeTypeIsUnion(): void
    {
        $resolvedSchema = $this->resolvedSchemaFor($this->mismatchFixture(), 'arrayOrDummyNativeDocblockList');

        $this->assertTrue($resolvedSchema->fromDocblock);
        $this->assertFalse($resolvedSchema->strict);
        $this->assertObjectSchema(DummyDTO::class, $resolvedSchema->schema[TypeHintResolver::ITEMS]);
    }

    public function testDocblockStrictModeCanBeEnabledOnProperty(): void
    {
        $resolvedSchema = $this->resolvedSchemaFor($this->strictModeFixture(), 'items');

        $this->assertTrue($resolvedSchema->fromDocblock);
        $this->assertTrue($resolvedSchema->strict);
    }

    public function testDocblockStrictModeCanBeEnabledOnParameter(): void
    {
        $constructor = new ReflectionMethod($this->strictModeFixture(), '__construct');
        $resolvedSchema = $this->typeSchemaResolver()->schema($constructor->getParameters()[1]);

        $this->assertTrue($resolvedSchema->fromDocblock);
        $this->assertTrue($resolvedSchema->strict);
    }

    public function testDocblockSchemaBuildsUnknownObjectWithoutClassFqcn(): void
    {
        $schema = $this->schemaFor($this->unknownFixture(), 'unknownDto');

        $this->assertSame(TypeHintResolver::OBJECT->value, $schema[TypeHintResolver::TYPE]);
        $this->assertArrayNotHasKey(TypeHintResolver::CLASS_FQCN, $schema);
    }

    public function testDocblockSchemaBuildsUnknownClassMapWithoutClassFqcn(): void
    {
        $schema = $this->schemaFor($this->unknownFixture(), 'unknownClassMap');
        $valueSchema = $schema[TypeHintResolver::ADDITIONAL_PROPERTIES];

        $this->assertSame(TypeHintResolver::OBJECT->value, $valueSchema[TypeHintResolver::TYPE]);
        $this->assertArrayNotHasKey(TypeHintResolver::CLASS_FQCN, $valueSchema);
    }

    private function nestedFixture(): object
    {
        return new class {
            /** @var array<string, \Ufo\DTO\Tests\Fixtures\DTO\DummyDTO[]> */
            public array $items;
        };
    }

    private function constructorFixture(): object
    {
        return new class {
            /**
             * @param string $name
             * @param \Ufo\DTO\Tests\Fixtures\DTO\DummyDTO[] $items
             */
            public function __construct(
                public string $name = '',
                public array $items = [],
            ) {}
        };
    }

    private function mismatchFixture(): object
    {
        return new class {
            /** @var \Ufo\DTO\Tests\Fixtures\DTO\DummyDTO[] */
            public mixed $mixedNativeDocblockDummyList;

            /** @var \Ufo\DTO\Tests\Fixtures\DTO\DummyDTO[] */
            public array|\Ufo\DTO\Tests\Fixtures\DTO\DummyDTO $arrayOrDummyNativeDocblockList;
        };
    }

    private function strictModeFixture(): object
    {
        return new class {
            /**
             * @var array<string, \Ufo\DTO\Tests\Fixtures\DTO\DummyDTO[]>
             * @strictMode true
             */
            public array $items;

            /**
             * @param string $name
             * @param \Ufo\DTO\Tests\Fixtures\DTO\DummyDTO[] $paramItems
             * @strictMode paramItems true
             */
            public function __construct(
                public string $name = '',
                public array $paramItems = [],
            ) {
            }
        };
    }

    private function unknownFixture(): object
    {
        return new class {
            /** @var UnknownDto */
            public mixed $unknownDto;

            /** @var array<string, UnknownClass> */
            public array $unknownClassMap;
        };
    }
}
