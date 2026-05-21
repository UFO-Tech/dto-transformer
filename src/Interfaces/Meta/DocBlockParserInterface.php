<?php

declare(strict_types=1);

namespace Ufo\DTO\Interfaces\Meta;

use Ufo\DTO\Transformer\Metadata\DocBlockMetadata;

interface DocBlockParserInterface
{
    public function parse(?string $docComment): DocBlockMetadata;
}

