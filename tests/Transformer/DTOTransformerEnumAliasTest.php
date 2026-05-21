<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer;

use PHPUnit\Framework\TestCase;
use Ufo\DTO\DTOTransformer;
use Ufo\DTO\Tests\Fixtures\DTO\EnumAliasDTO;
use Ufo\DTO\Tests\Fixtures\Enum\StringEnum;
use Ufo\DTO\Tests\InitDTOTransformerTrait;

final class DTOTransformerEnumAliasTest extends TestCase
{
    use InitDTOTransformerTrait;

    public function testFromArrayHydratesEnumReferencedThroughNamespaceAlias(): void
    {
        $dto = DTOTransformer::fromArray(EnumAliasDTO::class, [
            'id' => 'position-1',
            'role' => 'b',
            'userId' => null,
        ]);

        $this->assertInstanceOf(EnumAliasDTO::class, $dto);
        $this->assertSame('position-1', $dto->id);
        $this->assertSame(StringEnum::B, $dto->role);
        $this->assertNull($dto->userId);
        $this->assertFalse($dto->isVacant);
    }
}
