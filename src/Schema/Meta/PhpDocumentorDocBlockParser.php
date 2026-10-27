<?php

declare(strict_types=1);

namespace Ufo\DTO\Schema\Meta;

use phpDocumentor\Reflection\DocBlockFactoryInterface;
use Throwable;
use function trim;

/**
 * Розбір docblock без кешу; кешує {@see Cache\CachedDocBlockParser}.
 */
class PhpDocumentorDocBlockParser implements DocBlockParserInterface
{
    public function __construct(
        protected DocBlockFactoryInterface $factory,
    ) {}

    public function parse(?string $docComment): DocBlockMetadata
    {
        if ($docComment === null || trim($docComment) === '') {
            return new DocBlockMetadata($docComment);
        }

        try {
            return new DocBlockMetadata($docComment, $this->factory->create($docComment));
        } catch (Throwable) {
            return new DocBlockMetadata($docComment);
        }
    }
}
