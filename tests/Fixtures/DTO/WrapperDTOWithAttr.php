<?php

declare(strict_types = 1);

namespace Ufo\DTO\Tests\Fixtures\DTO;

use Ufo\DTO\ArrayConstructibleTrait;
use Ufo\DTO\ArrayConvertibleTrait;
use Ufo\DTO\Attributes\AttrDTO;
use Ufo\DTO\Contract\ArrayConstructibleInterface;
use Ufo\DTO\Contract\ArrayConvertibleInterface;

class WrapperDTOWithAttr  implements ArrayConstructibleInterface, ArrayConvertibleInterface
{
    use ArrayConstructibleTrait, ArrayConvertibleTrait;

    public function __construct(
        #[AttrDTO(dtoFQCN: DummyDTO::class, context: [
            AttrDTO::C_COLLECTION => true,
            AttrDTO::C_STRICT => true,
        ])]
        public DummyDTO|array $items
    ) {}
}
