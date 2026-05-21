<?php

namespace Ufo\DTO\Tests;

use Ufo\DTO\DTOTransformer;
use Ufo\DTO\Tests\Support\TransformerFactory;

trait InitDTOTransformerTrait
{
    protected function setUp(): void
    {
        if (!DTOTransformer::isInitialized()) {
            $dto = TransformerFactory::transformer();
            DTOTransformer::boot($dto);
        }
    }
}
