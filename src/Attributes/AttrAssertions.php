<?php

namespace Ufo\DTO\Attributes;

use Symfony\Component\Validator\Constraint;

abstract class AttrAssertions
{
    /**
     * @param Constraint[] $assertions
     */
    public function __construct(
        public array $assertions
    ) {}
}