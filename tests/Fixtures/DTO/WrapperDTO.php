<?php

declare(strict_types = 1);

namespace Ufo\DTO\Tests\Fixtures\DTO;

use Ufo\DTO\ArrayConstructibleTrait;
use Ufo\DTO\ArrayConvertibleTrait;
use Ufo\DTO\Attributes\AttrDTO;
use Ufo\DTO\Contract\ArrayConstructibleInterface;
use Ufo\DTO\Contract\ArrayConvertibleInterface;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;

class WrapperDTO  implements ArrayConstructibleInterface, ArrayConvertibleInterface
{
    use ArrayConstructibleTrait, ArrayConvertibleTrait;

    /**
     * @param DummyDTO|DummyDTO[] $items
     */
    public function __construct(
        public DummyDTO|array $items
    ) {}
}