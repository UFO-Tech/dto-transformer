<?php

declare(strict_types=1);

namespace Ufo\DTO\Schema\Meta\Cache;

use ReflectionParameter;
use ReflectionProperty;
use Ufo\DTO\Schema\Meta\ReflectionMetadataKeyGenerator;
use Ufo\DTO\Schema\Meta\RuntimeReflectionCache;
use Ufo\DTO\Schema\Meta\StrictModeResolverInterface;

use function sprintf;

/**
 * Strict mode властивості / параметра в runtime- і persistent-кеші.
 */
class CachedStrictModeResolver implements StrictModeResolverInterface
{
    protected const string KEY_FORMAT = '%s%s%s';
    protected const string CACHE_STRICT_MODE_PREFIX = 'strict_mode.';
    protected const string CACHE_DEFAULT_TRUE_SUFFIX = '.1';
    protected const string CACHE_DEFAULT_FALSE_SUFFIX = '.0';

    public function __construct(
        protected StrictModeResolverInterface $inner,
        protected ReflectionMetadataKeyGenerator $keyGenerator,
        protected RuntimeReflectionCache $runtimeCache,
    ) {}

    public function resolve(
        ReflectionProperty|ReflectionParameter $reflection,
        bool $default = false,
    ): bool
    {
        return $this->runtimeCache->rememberPersistent(
            sprintf(
                static::KEY_FORMAT,
                static::CACHE_STRICT_MODE_PREFIX,
                $this->keyGenerator->reflectionKey($reflection),
                $default ? static::CACHE_DEFAULT_TRUE_SUFFIX : static::CACHE_DEFAULT_FALSE_SUFFIX,
            ),
            fn (): bool => $this->inner->resolve($reflection, $default),
        );
    }
}
