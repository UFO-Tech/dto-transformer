<?php

namespace Ufo\DTO\Tests\Support;

use Ufo\DTO\DTOTransformer;

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
