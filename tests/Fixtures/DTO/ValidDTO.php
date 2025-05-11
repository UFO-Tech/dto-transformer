<?php

namespace Ufo\DTO\Tests\Fixtures\DTO;

use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Ufo\DTO\Attributes\AttrAssertions;

class ValidDTO
{

    public function __construct(
        #[AttrAssertions([new NotBlank(), new Length(['min' => 3])])]
        public string $name
    ) {}

}