<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Fixtures\DTO;

use Ufo\DTO\Attributes\AttrDTO;

final class DtoDefaultsDTO
{
    public function __construct(
        public DummyDTO $plain = new DummyDTO(1, 'plain-default'),
        public ?DummyDTO $nullable = new DummyDTO(2, 'nullable-default'),
        #[AttrDTO(DummyDTO::class)]
        public DummyDTO $withAttr = new DummyDTO(3, 'attr-default'),
        public string $title = 'untitled',
    ) {}
}
