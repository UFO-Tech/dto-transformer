<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Type;

use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;
use Ufo\DTO\Tests\Fixtures\DTO\UserDto;

final class DeepCollectionSchemaTest extends ReflectionTypeSchemaResolverTestCase
{
    public function testDocblockSchemaBuildsDeepAssocDtoMap(): void
    {
        $schema = $this->schemaFor($this->fixture(), 'deepAssocDummy');
        $innerMap = $schema[TypeHintResolver::ADDITIONAL_PROPERTIES];

        $this->assertObjectSchema(DummyDTO::class, $innerMap[TypeHintResolver::ADDITIONAL_PROPERTIES]);
    }

    public function testDocblockSchemaBuildsDeepAssocDtoListMap(): void
    {
        $schema = $this->schemaFor($this->fixture(), 'deepAssocDummyLists');
        $listSchema = $schema[TypeHintResolver::ADDITIONAL_PROPERTIES][TypeHintResolver::ADDITIONAL_PROPERTIES];

        $this->assertSame(TypeHintResolver::ARRAY->value, $listSchema[TypeHintResolver::TYPE]);
        $this->assertObjectSchema(DummyDTO::class, $listSchema[TypeHintResolver::ITEMS]);
    }

    public function testDocblockSchemaBuildsVeryDeepAssocDtoMap(): void
    {
        $schema = $this->schemaFor($this->fixture(), 'veryDeepAssocDummy');
        $levelTwo = $schema[TypeHintResolver::ADDITIONAL_PROPERTIES][TypeHintResolver::ADDITIONAL_PROPERTIES];

        $this->assertObjectSchema(DummyDTO::class, $levelTwo[TypeHintResolver::ADDITIONAL_PROPERTIES]);
    }

    public function testDocblockSchemaBuildsDeepAssocIntUnion(): void
    {
        $schema = $this->schemaFor($this->fixture(), 'deepAssocIntUnion');
        $valueUnion = $schema[TypeHintResolver::ADDITIONAL_PROPERTIES][TypeHintResolver::ITEMS][TypeHintResolver::ONE_OFF];

        $this->assertObjectSchema(DummyDTO::class, $valueUnion[0]);
        $this->assertObjectSchema(UserDto::class, $valueUnion[1]);
    }

    public function testDocblockSchemaBuildsDeepIntDtoMap(): void
    {
        $schema = $this->schemaFor($this->fixture(), 'deepIntDummy');
        $levelThreeItem = $schema[TypeHintResolver::ITEMS][TypeHintResolver::ITEMS][TypeHintResolver::ITEMS];

        $this->assertObjectSchema(DummyDTO::class, $levelThreeItem);
    }

    private function fixture(): object
    {
        return new class {
            /** @var array<string, array<string, \Ufo\DTO\Tests\Fixtures\DTO\DummyDTO>> */
            public array $deepAssocDummy;

            /** @var array<string, array<string, \Ufo\DTO\Tests\Fixtures\DTO\DummyDTO[]>> */
            public array $deepAssocDummyLists;

            /** @var array<string, array<string, array<string, \Ufo\DTO\Tests\Fixtures\DTO\DummyDTO>>> */
            public array $veryDeepAssocDummy;

            /** @var array<string, array<int, \Ufo\DTO\Tests\Fixtures\DTO\DummyDTO|\Ufo\DTO\Tests\Fixtures\DTO\UserDto>> */
            public array $deepAssocIntUnion;

            /** @var array<int, array<int, array<int, \Ufo\DTO\Tests\Fixtures\DTO\DummyDTO>>> */
            public array $deepIntDummy;
        };
    }
}
