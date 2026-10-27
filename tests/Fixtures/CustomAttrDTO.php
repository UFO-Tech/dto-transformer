<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Fixtures;

use Attribute;
use Ufo\DTO\Attributes\AttrDTO;

#[Attribute(Attribute::TARGET_METHOD|Attribute::TARGET_PROPERTY|Attribute::TARGET_PARAMETER)]
final class CustomAttrDTO extends AttrDTO
{
}
