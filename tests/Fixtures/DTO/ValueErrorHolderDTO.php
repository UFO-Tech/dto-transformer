<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Fixtures\DTO;

use Ufo\DTO\Attributes\AttrDTO;
use Ufo\DTO\Tests\Fixtures\ValueErrorTransformer;

final class ValueErrorHolderDTO
{
    public function __construct(
        #[AttrDTO(DummyDTO::class, context: [AttrDTO::C_TRANSFORMER => ValueErrorTransformer::class])]
        public mixed $item = null,
    ) {}
}
