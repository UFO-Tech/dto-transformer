<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Fixtures\DTO;

use Ufo\DTO\Attributes\AttrDTO;

class SmartInheritedAttrDTO
{
    public function __construct(
        #[AttrDTO(DummyDTO::class)]
        public object $friend,
    ) {}
}
