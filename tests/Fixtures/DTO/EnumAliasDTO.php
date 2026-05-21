<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Fixtures\DTO;

use Ufo\DTO\Tests\Fixtures\Enum as Enums;

final class EnumAliasDTO
{
    public string $id;
    public Enums\StringEnum $role;
    public ?string $userId;
    public bool $isVacant = false;
}
