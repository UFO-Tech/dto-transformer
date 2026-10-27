<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Fixtures\DTO;

use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotNull;
use Ufo\DTO\Attributes\AttrAssertions;

final class AssertedDefaultsDTO
{
    public function __construct(
        #[AttrAssertions([new Length(min: 3)])]
        public string $optional = 'default',
        #[AttrAssertions([new NotNull()])]
        public ?string $notNull = 'present',
    ) {}
}
