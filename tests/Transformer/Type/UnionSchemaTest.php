<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Type;

use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;
use Ufo\DTO\Tests\Fixtures\DTO\UserDto;

final class UnionSchemaTest extends ReflectionTypeSchemaResolverTestCase
{
    public function testDocblockSchemaBuildsDtoUnion(): void
    {
        $resolvedSchema = $this->resolvedSchemaFor($this->fixture(), 'userOrDummy');
        $oneOf = $resolvedSchema->schema[TypeHintResolver::ONE_OFF];

        $this->assertTrue($resolvedSchema->fromDocblock);
        $this->assertFalse($resolvedSchema->strict);
        $this->assertCount(2, $oneOf);
        $this->assertObjectSchema(UserDto::class, $oneOf[0]);
        $this->assertObjectSchema(DummyDTO::class, $oneOf[1]);
    }

    public function testDocblockSchemaBuildsNullableDtoUnion(): void
    {
        $schema = $this->schemaFor($this->fixture(), 'userOrDummyOrNull');
        $oneOf = $schema[TypeHintResolver::ONE_OFF];

        $this->assertCount(3, $oneOf);
        $this->assertObjectSchema(UserDto::class, $oneOf[0]);
        $this->assertObjectSchema(DummyDTO::class, $oneOf[1]);
        $this->assertSame(TypeHintResolver::NULL->value, $oneOf[2][TypeHintResolver::TYPE]);
    }

    public function testDocblockSchemaBuildsDtoOrArrayUnion(): void
    {
        $schema = $this->schemaFor($this->fixture(), 'userOrArray');
        $oneOf = $schema[TypeHintResolver::ONE_OFF];

        $this->assertObjectSchema(UserDto::class, $oneOf[0]);
        $this->assertSame(TypeHintResolver::ARRAY->value, $oneOf[1][TypeHintResolver::TYPE]);
    }

    public function testDocblockSchemaBuildsDtoOrMixedUnion(): void
    {
        $schema = $this->schemaFor($this->fixture(), 'userOrMixed');
        $oneOf = $schema[TypeHintResolver::ONE_OFF];

        $this->assertObjectSchema(UserDto::class, $oneOf[0]);
        $this->assertSame(TypeHintResolver::mixedForJsonSchema(), $oneOf[1][TypeHintResolver::ONE_OFF]);
    }

    public function testDocblockSchemaBuildsDtoOrStringUnion(): void
    {
        $schema = $this->schemaFor($this->fixture(), 'userOrString');
        $oneOf = $schema[TypeHintResolver::ONE_OFF];

        $this->assertObjectSchema(UserDto::class, $oneOf[0]);
        $this->assertSame(TypeHintResolver::STRING->value, $oneOf[1][TypeHintResolver::TYPE]);
    }

    public function testDocblockSchemaBuildsDtoAndScalarUnion(): void
    {
        $schema = $this->schemaFor($this->fixture(), 'userOrIntOrString');
        $oneOf = $schema[TypeHintResolver::ONE_OFF];

        $this->assertObjectSchema(UserDto::class, $oneOf[0]);
        $this->assertSame(TypeHintResolver::INTEGER->value, $oneOf[1][TypeHintResolver::TYPE]);
        $this->assertSame(TypeHintResolver::STRING->value, $oneOf[2][TypeHintResolver::TYPE]);
    }

    private function fixture(): object
    {
        return new class {
            /** @var \Ufo\DTO\Tests\Fixtures\DTO\UserDto|\Ufo\DTO\Tests\Fixtures\DTO\DummyDTO */
            public mixed $userOrDummy;

            /** @var \Ufo\DTO\Tests\Fixtures\DTO\UserDto|\Ufo\DTO\Tests\Fixtures\DTO\DummyDTO|null */
            public mixed $userOrDummyOrNull;

            /** @var \Ufo\DTO\Tests\Fixtures\DTO\UserDto|array */
            public mixed $userOrArray;

            /** @var \Ufo\DTO\Tests\Fixtures\DTO\UserDto|mixed */
            public mixed $userOrMixed;

            /** @var \Ufo\DTO\Tests\Fixtures\DTO\UserDto|string */
            public mixed $userOrString;

            /** @var \Ufo\DTO\Tests\Fixtures\DTO\UserDto|int|string */
            public mixed $userOrIntOrString;
        };
    }
}
