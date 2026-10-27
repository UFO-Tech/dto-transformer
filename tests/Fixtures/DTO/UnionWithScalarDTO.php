<?php

declare(strict_types = 1);

namespace Ufo\DTO\Tests\Fixtures\DTO;

use Ufo\DTO\ArrayConstructibleTrait;
use Ufo\DTO\ArrayConvertibleTrait;
use Ufo\DTO\Contract\ArrayConstructibleInterface;
use Ufo\DTO\Contract\ArrayConvertibleInterface;

class UnionWithScalarDTO implements ArrayConstructibleInterface, ArrayConvertibleInterface
{
    use ArrayConstructibleTrait;
    use ArrayConvertibleTrait;

    public UserDto|string $value1;
    public DummyDTO|array|null $value2 = null;
    public int|UserDto|null $value3;
}