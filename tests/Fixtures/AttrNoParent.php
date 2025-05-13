<?php

namespace Ufo\DTO\Tests\Fixtures;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD|Attribute::TARGET_PROPERTY|Attribute::TARGET_PARAMETER)]
final class AttrNoParent {}