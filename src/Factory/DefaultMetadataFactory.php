<?php

declare(strict_types=1);

namespace Ufo\DTO\Factory;

use phpDocumentor\Reflection\DocBlockFactory;
use Symfony\Contracts\Cache\CacheInterface;
use Ufo\DTO\Interfaces\Meta\DocBlockParserInterface;
use Ufo\DTO\Interfaces\Meta\ReflectionMetadataKeyGeneratorInterface;
use Ufo\DTO\Interfaces\Meta\RuntimeReflectionCacheInterface;
use Ufo\DTO\Transformer\Metadata\PhpDocumentorDocBlockParser;
use Ufo\DTO\Transformer\Metadata\ReflectionMetadataKeyGenerator;
use Ufo\DTO\Transformer\Metadata\RuntimeReflectionCache;

class DefaultMetadataFactory
{
    public function createRuntimeReflectionCache(?CacheInterface $cache = null): RuntimeReflectionCacheInterface
    {
        return new RuntimeReflectionCache(persistentCache: $cache);
    }

    public function createDocBlockParser(
        ?RuntimeReflectionCacheInterface $runtimeCache = null,
    ): DocBlockParserInterface
    {
        return new PhpDocumentorDocBlockParser(DocBlockFactory::createInstance(), $runtimeCache);
    }

    public function createReflectionMetadataKeyGenerator(): ReflectionMetadataKeyGeneratorInterface
    {
        return new ReflectionMetadataKeyGenerator();
    }
}
