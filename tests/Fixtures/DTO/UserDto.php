<?php

namespace Ufo\DTO\Tests\Fixtures\DTO;

use Ufo\DTO\ArrayConstructibleTrait;
use Ufo\DTO\ArrayConvertibleTrait;
use Ufo\DTO\Interfaces\IArrayConstructible;
use Ufo\DTO\Interfaces\IArrayConvertible;

use function time;

class UserDto implements IArrayConstructible, IArrayConvertible
{
    use ArrayConstructibleTrait;
    use ArrayConvertibleTrait;
    public readonly int $currentTime;

    public function __construct(
        public string $name,
        public string $email,
    )
    {
        $this->currentTime = time();
    }
}
