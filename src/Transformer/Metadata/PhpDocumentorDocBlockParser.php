<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Metadata;

use phpDocumentor\Reflection\DocBlockFactoryInterface;
use Throwable;
use Ufo\DTO\Interfaces\Meta\DocBlockParserInterface;
use Ufo\DTO\Interfaces\Meta\RuntimeReflectionCacheInterface;
use function md5;
use function trim;

class PhpDocumentorDocBlockParser implements DocBlockParserInterface
{
    protected const string RUNTIME_DOC_BLOCK_PARSE_PREFIX = 'runtime.doc_block.parse.';

    public function __construct(
        protected DocBlockFactoryInterface $factory,
        protected RuntimeReflectionCacheInterface $runtimeCache,
    ) {}

    public function parse(?string $docComment): DocBlockMetadata
    {
        if ($docComment === null || trim($docComment) === '') {
            return new DocBlockMetadata($docComment);
        }

        return $this->runtimeCache()->remember(
            static::RUNTIME_DOC_BLOCK_PARSE_PREFIX . md5($docComment),
            fn (): DocBlockMetadata => $this->parseUncached($docComment),
        );
    }

    protected function parseUncached(string $docComment): DocBlockMetadata
    {
        try {
            return new DocBlockMetadata($docComment, $this->factory->create($docComment));
        } catch (Throwable) {
            return new DocBlockMetadata($docComment);
        }
    }

    protected function runtimeCache(): RuntimeReflectionCacheInterface
    {
        return $this->runtimeCache;
    }
}
