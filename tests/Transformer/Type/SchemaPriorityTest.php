<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Type;

use Ufo\DTO\Attributes\AttrDTO;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;
use Ufo\DTO\Tests\Fixtures\DTO\DTOWithEnumValue;
use Ufo\DTO\Tests\Fixtures\DTO\UserDto;

final class SchemaPriorityTest extends ReflectionTypeSchemaResolverTestCase
{
    public function testSchemaPrefersDocblockOverNativeType(): void
    {
        $resolvedSchema = $this->resolvedSchemaFor($this->attrPriorityFixture(), 'value');

        $this->assertTrue($resolvedSchema->fromDocblock);
        $this->assertFalse($resolvedSchema->strict);
        $this->assertObjectSchema(UserDto::class, $resolvedSchema->schema);
    }

    public function testSchemaFallsBackToNativeType(): void
    {
        $resolvedSchema = $this->resolvedSchemaFor(DTOWithEnumValue::class, 'stringEnum');

        $this->assertFalse($resolvedSchema->fromDocblock);
        $this->assertTrue($resolvedSchema->strict);
        $this->assertEnumSchema('StringEnum', ['a', 'b', 'c'], $resolvedSchema->schema);
    }

    private function attrPriorityFixture(): object
    {
        return new class {
            /**
             * @var \Ufo\DTO\Tests\Fixtures\DTO\UserDto
             */
            #[AttrDTO(DummyDTO::class)]
            public DummyDTO $value;
        };
    }
}
