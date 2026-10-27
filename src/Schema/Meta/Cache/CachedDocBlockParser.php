<?php

declare(strict_types=1);

namespace Ufo\DTO\Schema\Meta\Cache;

use Ufo\DTO\Schema\Meta\DocBlockMetadata;
use Ufo\DTO\Schema\Meta\DocBlockParserInterface;
use Ufo\DTO\Schema\Meta\RuntimeReflectionCache;

use function md5;

/**
 * Розібраний docblock у runtime-кеші за текстом коментаря.
 */
class CachedDocBlockParser implements DocBlockParserInterface
{
    protected const string RUNTIME_DOC_BLOCK_PARSE_PREFIX = 'runtime.doc_block.parse.';

    public function __construct(
        protected DocBlockParserInterface $inner,
        protected RuntimeReflectionCache $runtimeCache,
    ) {}

    public function parse(?string $docComment): DocBlockMetadata
    {
        if ($docComment === null || $docComment === '') {
            return $this->inner->parse($docComment);
        }

        return $this->runtimeCache->remember(
            static::RUNTIME_DOC_BLOCK_PARSE_PREFIX . md5($docComment),
            fn (): DocBlockMetadata => $this->inner->parse($docComment),
        );
    }
}
