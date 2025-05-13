<?php

namespace Ufo\DTO\Tests\Fixtures\DTO;

use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Ufo\DTO\ArrayConstructibleTrait;
use Ufo\DTO\Attributes\AttrAssertions;
use Ufo\DTO\Interfaces\IArrayConstructible;

class ValidDTO implements IArrayConstructible
{
    use ArrayConstructibleTrait;

    public function __construct(
        #[AttrAssertions([new NotBlank(), new Length(['min' => 3])])]
        public string $name
    ) {}

}