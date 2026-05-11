<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Type;

use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;

final class ScalarMixedSchemaTest extends ReflectionTypeSchemaResolverTestCase
{
    public function testDocblockSchemaBuildsScalarUnion(): void
    {
        $schema = $this->schemaFor($this->fixture(), 'intOrString');
        $oneOf = $schema[TypeHintResolver::ONE_OFF];

        $this->assertSame(TypeHintResolver::INTEGER->value, $oneOf[0][TypeHintResolver::TYPE]);
        $this->assertSame(TypeHintResolver::STRING->value, $oneOf[1][TypeHintResolver::TYPE]);
    }

    public function testDocblockSchemaBuildsNullableScalarUnion(): void
    {
        $schema = $this->schemaFor($this->fixture(), 'intOrStringOrNull');
        $oneOf = $schema[TypeHintResolver::ONE_OFF];

        $this->assertSame(TypeHintResolver::INTEGER->value, $oneOf[0][TypeHintResolver::TYPE]);
        $this->assertSame(TypeHintResolver::STRING->value, $oneOf[1][TypeHintResolver::TYPE]);
        $this->assertSame(TypeHintResolver::NULL->value, $oneOf[2][TypeHintResolver::TYPE]);
    }

    public function testDocblockSchemaBuildsBoolOrIntUnion(): void
    {
        $schema = $this->schemaFor($this->fixture(), 'boolOrInt');
        $oneOf = $schema[TypeHintResolver::ONE_OFF];

        $this->assertSame(TypeHintResolver::BOOLEAN->value, $oneOf[0][TypeHintResolver::TYPE]);
        $this->assertSame(TypeHintResolver::INTEGER->value, $oneOf[1][TypeHintResolver::TYPE]);
    }

    public function testDocblockSchemaBuildsFloatOrIntUnion(): void
    {
        $schema = $this->schemaFor($this->fixture(), 'floatOrInt');
        $oneOf = $schema[TypeHintResolver::ONE_OFF];

        $this->assertSame(TypeHintResolver::NUMBER->value, $oneOf[0][TypeHintResolver::TYPE]);
        $this->assertSame(TypeHintResolver::INTEGER->value, $oneOf[1][TypeHintResolver::TYPE]);
    }

    public function testDocblockSchemaBuildsMixed(): void
    {
        $schema = $this->schemaFor($this->fixture(), 'mixedValue');

        $this->assertSame(TypeHintResolver::mixedForJsonSchema(), $schema[TypeHintResolver::ONE_OFF]);
    }

    public function testDocblockSchemaBuildsAssocMixedMap(): void
    {
        $schema = $this->schemaFor($this->fixture(), 'assocMixed');

        $this->assertSame(
            TypeHintResolver::mixedForJsonSchema(),
            $schema[TypeHintResolver::ADDITIONAL_PROPERTIES][TypeHintResolver::ONE_OFF],
        );
    }

    public function testDocblockSchemaBuildsMixedList(): void
    {
        $schema = $this->schemaFor($this->fixture(), 'mixedList');

        $this->assertSame(TypeHintResolver::mixedForJsonSchema(), $schema[TypeHintResolver::ITEMS][TypeHintResolver::ONE_OFF]);
    }

    public function testDocblockSchemaBuildsMixedOrDtoUnion(): void
    {
        $schema = $this->schemaFor($this->fixture(), 'mixedOrDummy');
        $oneOf = $schema[TypeHintResolver::ONE_OFF];

        $this->assertSame(TypeHintResolver::mixedForJsonSchema(), $oneOf[0][TypeHintResolver::ONE_OFF]);
        $this->assertObjectSchema(DummyDTO::class, $oneOf[1]);
    }

    private function fixture(): object
    {
        return new class {
            /** @var int|string */
            public mixed $intOrString;

            /** @var int|string|null */
            public mixed $intOrStringOrNull;

            /** @var bool|int */
            public mixed $boolOrInt;

            /** @var float|int */
            public mixed $floatOrInt;

            /** @var mixed */
            public mixed $mixedValue;

            /** @var array<string, mixed> */
            public array $assocMixed;

            /** @var array<mixed> */
            public array $mixedList;

            /** @var mixed|\Ufo\DTO\Tests\Fixtures\DTO\DummyDTO */
            public mixed $mixedOrDummy;
        };
    }
}
