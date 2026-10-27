<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Fixtures\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use Ufo\DTO\Attributes\AttrAssertions;
use Ufo\DTO\Attributes\AttrDTO;

final class AssertedAttrDtoCollectionDTO
{
    /**
     * @param DummyDTO[] $items
     */
    public function __construct(
        #[AttrAssertions([new Assert\Count(min: 2), new Assert\All([new Assert\Type('array')])])]
        #[AttrDTO(DummyDTO::class, context: [AttrDTO::C_COLLECTION => true, AttrDTO::C_STRICT => true])]
        public array $items,
    ) {}
}
