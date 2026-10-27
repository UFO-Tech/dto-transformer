<?php

declare(strict_types=1);

namespace Ufo\DTO\Schema\Meta;

interface DocBlockParserInterface
{
    public function parse(?string $docComment): DocBlockMetadata;
}
