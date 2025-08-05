<?php

declare(strict_types = 1);

namespace Ufo\DTO\Tests\Fixtures\DTO;

use Ufo\DTO\ArrayConstructibleTrait;
use Ufo\DTO\ArrayConvertibleTrait;
use Ufo\DTO\Attributes\AttrDTO;
use Ufo\DTO\Interfaces\IArrayConstructible;
use Ufo\DTO\Interfaces\IArrayConvertible;

class WrapperDTO  implements IArrayConstructible, IArrayConvertible
{
    use ArrayConstructibleTrait, ArrayConvertibleTrait;

    public function __construct(
        #[AttrDTO(dtoFQCN: DummyDTO::class, collection: true)]
        public DummyDTO|array $items
    ) {}
}