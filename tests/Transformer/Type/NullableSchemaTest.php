<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Type;

use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;

final class NullableSchemaTest extends ReflectionTypeSchemaResolverTestCase
{
    public function testDocblockSchemaBuildsNullableDto(): void
    {
        $schema = $this->schemaFor($this->fixture(), 'nullableDummy');
        $oneOf = $schema[TypeHintResolver::ONE_OFF];

        $this->assertObjectSchema(DummyDTO::class, $oneOf[0]);
        $this->assertSame(TypeHintResolver::NULL->value, $oneOf[1][TypeHintResolver::TYPE]);
    }

    public function testDocblockSchemaBuildsNullableAssocDtoMap(): void
    {
        $schema = $this->schemaFor($this->fixture(), 'nullableAssocDummy');
        $oneOf = $schema[TypeHintResolver::ONE_OFF];

        $this->assertObjectSchema(DummyDTO::class, $oneOf[0][TypeHintResolver::ADDITIONAL_PROPERTIES]);
        $this->assertSame(TypeHintResolver::NULL->value, $oneOf[1][TypeHintResolver::TYPE]);
    }

    public function testDocblockSchemaBuildsDtoListOrNull(): void
    {
        $schema = $this->schemaFor($this->fixture(), 'dummyListOrNull');
        $oneOf = $schema[TypeHintResolver::ONE_OFF];

        $this->assertObjectSchema(DummyDTO::class, $oneOf[0][TypeHintResolver::ITEMS]);
        $this->assertSame(TypeHintResolver::NULL->value, $oneOf[1][TypeHintResolver::TYPE]);
    }

    public function testDocblockSchemaBuildsAssocDtoMapOrNull(): void
    {
        $schema = $this->schemaFor($this->fixture(), 'assocDummyOrNull');
        $oneOf = $schema[TypeHintResolver::ONE_OFF];

        $this->assertObjectSchema(DummyDTO::class, $oneOf[0][TypeHintResolver::ADDITIONAL_PROPERTIES]);
        $this->assertSame(TypeHintResolver::NULL->value, $oneOf[1][TypeHintResolver::TYPE]);
    }

    private function fixture(): object
    {
        return new class {
            /** @var ?\Ufo\DTO\Tests\Fixtures\DTO\DummyDTO */
            public mixed $nullableDummy;

            /** @var array<string, \Ufo\DTO\Tests\Fixtures\DTO\DummyDTO>|null */
            public mixed $nullableAssocDummy;

            /** @var \Ufo\DTO\Tests\Fixtures\DTO\DummyDTO[]|null */
            public mixed $dummyListOrNull;

            /** @var array<string, \Ufo\DTO\Tests\Fixtures\DTO\DummyDTO>|null */
            public mixed $assocDummyOrNull;
        };
    }
}
