<?php

declare(strict_types=1);

namespace Ufo\DTO\Factory;

use phpDocumentor\Reflection\DocBlockFactory;
use Symfony\Contracts\Cache\CacheInterface;
use Ufo\DTO\Schema\Meta\Cache\CachedDocBlockParser;
use Ufo\DTO\Schema\Meta\Cache\CachedReflectionMetadataProvider;
use Ufo\DTO\Schema\Meta\Cache\CachedStrictModeResolver;
use Ufo\DTO\Schema\Meta\DocBlockParserInterface;
use Ufo\DTO\Schema\Meta\PhpDocumentorDocBlockParser;
use Ufo\DTO\Schema\Meta\ReflectionMetadataKeyGenerator;
use Ufo\DTO\Schema\Meta\ReflectionMetadataProvider;
use Ufo\DTO\Schema\Meta\ReflectionMetadataProviderInterface;
use Ufo\DTO\Schema\Meta\RuntimeReflectionCache;
use Ufo\DTO\Schema\Meta\StrictModeResolver;
use Ufo\DTO\Schema\Meta\StrictModeResolverInterface;

/**
 * Шар метаданих: базові реалізації без кешу, обгорнуті кешуючими декораторами, якщо передано runtime-кеш.
 */
class DefaultMetadataFactory
{
    public function createRuntimeReflectionCache(
        ?CacheInterface $cache = null,
        int $limit = RuntimeReflectionCache::DEFAULT_LIMIT,
    ): RuntimeReflectionCache
    {
        return new RuntimeReflectionCache(limit: $limit, persistentCache: $cache);
    }

    public function createReflectionMetadataKeyGenerator(): ReflectionMetadataKeyGenerator
    {
        return new ReflectionMetadataKeyGenerator();
    }

    public function createDocBlockParser(?RuntimeReflectionCache $runtimeCache = null): DocBlockParserInterface
    {
        $parser = new PhpDocumentorDocBlockParser(DocBlockFactory::createInstance());

        return $runtimeCache === null ? $parser : new CachedDocBlockParser($parser, $runtimeCache);
    }

    public function createMetadataProvider(
        ?RuntimeReflectionCache $runtimeCache = null,
        ?DocBlockParserInterface $docBlockParser = null,
    ): ReflectionMetadataProviderInterface
    {
        $provider = new ReflectionMetadataProvider($docBlockParser ?? $this->createDocBlockParser($runtimeCache));

        return $runtimeCache === null
            ? $provider
            : new CachedReflectionMetadataProvider($provider, $this->createReflectionMetadataKeyGenerator(), $runtimeCache);
    }

    public function createStrictModeResolver(
        ReflectionMetadataProviderInterface $metadataProvider,
        ?RuntimeReflectionCache $runtimeCache = null,
    ): StrictModeResolverInterface
    {
        $resolver = new StrictModeResolver($metadataProvider);

        return $runtimeCache === null
            ? $resolver
            : new CachedStrictModeResolver($resolver, $this->createReflectionMetadataKeyGenerator(), $runtimeCache);
    }
}
