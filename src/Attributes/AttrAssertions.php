<?php

namespace Ufo\DTO\Attributes;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute(Attribute::TARGET_METHOD|Attribute::TARGET_PROPERTY|Attribute::TARGET_PARAMETER)]
readonly class AttrAssertions
{
    /**
     * @param Constraint[] $assertions
     */
    public function __construct(
        public array $assertions
    ) {}
}