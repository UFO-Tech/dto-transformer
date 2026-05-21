<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Type;

use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;
use Ufo\DTO\Tests\Fixtures\DTO\UserDto;

final class CollectionSchemaTest extends ReflectionTypeSchemaResolverTestCase
{
    public function testDocblockSchemaBuildsListUnion(): void
    {
        $schema = $this->schemaFor($this->fixture(), 'userListOrDummyList');
        $oneOf = $schema[TypeHintResolver::ONE_OFF];

        $this->assertSame(TypeHintResolver::ARRAY->value, $oneOf[0][TypeHintResolver::TYPE]);
        $this->assertObjectSchema(UserDto::class, $oneOf[0][TypeHintResolver::ITEMS]);
        $this->assertObjectSchema(DummyDTO::class, $oneOf[1][TypeHintResolver::ITEMS]);
    }

    public function testDocblockSchemaBuildsGenericListUnion(): void
    {
        $schema = $this->schemaFor($this->fixture(), 'genericUserOrDummyList');
        $oneOf = $schema[TypeHintResolver::ONE_OFF];

        $this->assertObjectSchema(UserDto::class, $oneOf[0][TypeHintResolver::ITEMS]);
        $this->assertObjectSchema(DummyDTO::class, $oneOf[1][TypeHintResolver::ITEMS]);
    }

    public function testDocblockSchemaBuildsArrayOfCollectionUnion(): void
    {
        $schema = $this->schemaFor($this->fixture(), 'arrayOfUnionLists');
        $itemUnion = $schema[TypeHintResolver::ITEMS][TypeHintResolver::ONE_OFF];

        $this->assertObjectSchema(UserDto::class, $itemUnion[0][TypeHintResolver::ITEMS]);
        $this->assertObjectSchema(DummyDTO::class, $itemUnion[1][TypeHintResolver::ITEMS]);
    }

    public function testDocblockSchemaBuildsAssocCollectionUnion(): void
    {
        $schema = $this->schemaFor($this->fixture(), 'assocUnionLists');
        $valueUnion = $schema[TypeHintResolver::ADDITIONAL_PROPERTIES][TypeHintResolver::ONE_OFF];

        $this->assertObjectSchema(UserDto::class, $valueUnion[0][TypeHintResolver::ITEMS]);
        $this->assertObjectSchema(DummyDTO::class, $valueUnion[1][TypeHintResolver::ITEMS]);
    }

    public function testDocblockSchemaBuildsAssocDtoMapUnion(): void
    {
        $schema = $this->schemaFor($this->fixture(), 'assocUserOrDummy');
        $oneOf = $schema[TypeHintResolver::ONE_OFF];

        $this->assertObjectSchema(UserDto::class, $oneOf[0][TypeHintResolver::ADDITIONAL_PROPERTIES]);
        $this->assertObjectSchema(DummyDTO::class, $oneOf[1][TypeHintResolver::ADDITIONAL_PROPERTIES]);
    }

    public function testDocblockSchemaBuildsAssocDtoListMap(): void
    {
        $schema = $this->schemaFor($this->fixture(), 'assocDummyLists');
        $listSchema = $schema[TypeHintResolver::ADDITIONAL_PROPERTIES];

        $this->assertSame(TypeHintResolver::ARRAY->value, $listSchema[TypeHintResolver::TYPE]);
        $this->assertObjectSchema(DummyDTO::class, $listSchema[TypeHintResolver::ITEMS]);
    }

    public function testDocblockSchemaBuildsAssocDtoMap(): void
    {
        $schema = $this->schemaFor($this->fixture(), 'assocDummy');

        $this->assertObjectSchema(DummyDTO::class, $schema[TypeHintResolver::ADDITIONAL_PROPERTIES]);
    }

    public function testDocblockSchemaBuildsIntDtoMap(): void
    {
        $schema = $this->schemaFor($this->fixture(), 'intDummyMap');

        $this->assertSame(TypeHintResolver::ARRAY->value, $schema[TypeHintResolver::TYPE]);
        $this->assertObjectSchema(DummyDTO::class, $schema[TypeHintResolver::ITEMS]);
    }

    public function testDocblockSchemaBuildsArrayOfAssocDtoMap(): void
    {
        $schema = $this->schemaFor($this->fixture(), 'arrayOfAssocDummy');
        $itemSchema = $schema[TypeHintResolver::ITEMS];

        $this->assertObjectSchema(DummyDTO::class, $itemSchema[TypeHintResolver::ADDITIONAL_PROPERTIES]);
    }

    public function testDocblockSchemaBuildsArrayOfAssocDtoUnion(): void
    {
        $schema = $this->schemaFor($this->fixture(), 'arrayOfAssocUnion');
        $valueUnion = $schema[TypeHintResolver::ITEMS][TypeHintResolver::ADDITIONAL_PROPERTIES][TypeHintResolver::ONE_OFF];

        $this->assertObjectSchema(UserDto::class, $valueUnion[0]);
        $this->assertObjectSchema(DummyDTO::class, $valueUnion[1]);
    }

    public function testDocblockSchemaBuildsArrayOfAssocCollectionUnion(): void
    {
        $schema = $this->schemaFor($this->fixture(), 'arrayOfAssocUnionLists');
        $valueUnion = $schema[TypeHintResolver::ITEMS][TypeHintResolver::ADDITIONAL_PROPERTIES][TypeHintResolver::ONE_OFF];

        $this->assertObjectSchema(UserDto::class, $valueUnion[0][TypeHintResolver::ITEMS]);
        $this->assertObjectSchema(DummyDTO::class, $valueUnion[1][TypeHintResolver::ITEMS]);
    }

    private function fixture(): object
    {
        return new class {
            /** @var \Ufo\DTO\Tests\Fixtures\DTO\UserDto[]|\Ufo\DTO\Tests\Fixtures\DTO\DummyDTO[] */
            public array $userListOrDummyList;

            /** @var array<\Ufo\DTO\Tests\Fixtures\DTO\UserDto>|array<\Ufo\DTO\Tests\Fixtures\DTO\DummyDTO> */
            public array $genericUserOrDummyList;

            /** @var array<\Ufo\DTO\Tests\Fixtures\DTO\UserDto[]|\Ufo\DTO\Tests\Fixtures\DTO\DummyDTO[]> */
            public array $arrayOfUnionLists;

            /** @var array<string, \Ufo\DTO\Tests\Fixtures\DTO\UserDto[]|\Ufo\DTO\Tests\Fixtures\DTO\DummyDTO[]> */
            public array $assocUnionLists;

            /** @var array<string, \Ufo\DTO\Tests\Fixtures\DTO\UserDto>|array<string, \Ufo\DTO\Tests\Fixtures\DTO\DummyDTO> */
            public array $assocUserOrDummy;

            /** @var array<string, \Ufo\DTO\Tests\Fixtures\DTO\DummyDTO[]> */
            public array $assocDummyLists;

            /** @var array<string, \Ufo\DTO\Tests\Fixtures\DTO\DummyDTO> */
            public array $assocDummy;

            /** @var array<int, \Ufo\DTO\Tests\Fixtures\DTO\DummyDTO> */
            public array $intDummyMap;

            /** @var array<array<string, \Ufo\DTO\Tests\Fixtures\DTO\DummyDTO>> */
            public array $arrayOfAssocDummy;

            /** @var array<array<string, \Ufo\DTO\Tests\Fixtures\DTO\UserDto|\Ufo\DTO\Tests\Fixtures\DTO\DummyDTO>> */
            public array $arrayOfAssocUnion;

            /** @var array<array<string, \Ufo\DTO\Tests\Fixtures\DTO\UserDto[]|\Ufo\DTO\Tests\Fixtures\DTO\DummyDTO[]>> */
            public array $arrayOfAssocUnionLists;
        };
    }
}
