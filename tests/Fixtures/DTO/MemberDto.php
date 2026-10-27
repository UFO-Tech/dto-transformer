<?php

namespace Ufo\DTO\Tests\Fixtures\DTO;

use Ufo\DTO\ArrayConstructibleTrait;
use Ufo\DTO\ArrayConvertibleTrait;
use Ufo\DTO\Contract\ArrayConstructibleInterface;
use Ufo\DTO\Contract\ArrayConvertibleInterface;

use function time;

class MemberDto implements ArrayConstructibleInterface, ArrayConvertibleInterface
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
