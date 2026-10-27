<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Fixtures\DTO;

use Ufo\DTO\Attributes\AttrDTO;

final class RequiredAttrDTO
{
    public function __construct(
        #[AttrDTO(DummyDTO::class)]
        public DummyDTO $single = new DummyDTO(0, 'default'),
        #[AttrDTO(DummyDTO::class, context: [AttrDTO::C_COLLECTION => true])]
        public array $items = [],
        #[AttrDTO(DummyDTO::class, context: [AttrDTO::C_COLLECTION => true, AttrDTO::C_STRICT => true])]
        public array $strictItems = [],
    ) {}
}
