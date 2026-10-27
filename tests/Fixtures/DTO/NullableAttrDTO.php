<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Fixtures\DTO;

use Ufo\DTO\Attributes\AttrDTO;

final class NullableAttrDTO
{
    public function __construct(
        #[AttrDTO(DummyDTO::class)]
        public ?DummyDTO $single = null,
        #[AttrDTO(DummyDTO::class, context: [AttrDTO::C_COLLECTION => true])]
        public ?array $items = null,
        #[AttrDTO(DummyDTO::class, context: [AttrDTO::C_COLLECTION => true, AttrDTO::C_STRICT => true])]
        public ?array $strictItems = null,
        #[AttrDTO(DummyDTO::class)]
        public mixed $untyped = null,
        #[AttrDTO(DummyDTO::class, context: [AttrDTO::C_COLLECTION => true])]
        public mixed $looseAny = null,
    ) {}
}
