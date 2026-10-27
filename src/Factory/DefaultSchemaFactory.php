<?php

declare(strict_types=1);

namespace Ufo\DTO\Factory;

use Symfony\Contracts\Cache\CacheInterface;
use Ufo\DTO\Schema\Cache\CachedClassSchemaDescriber;
use Ufo\DTO\Schema\Cache\CachedClassSchemaProvider;
use Ufo\DTO\Schema\ClassSchemaFactory;
use Ufo\DTO\Schema\ClassSchemaProvider;
use Ufo\DTO\Schema\ClassSchemaProviderInterface;
use Ufo\DTO\Schema\DefaultValueResolver;
use Ufo\DTO\Schema\Meta\ReflectionMetadataProviderInterface;
use Ufo\DTO\Schema\Meta\SerializationContextAttributeReader;
use Ufo\DTO\Schema\Type\NamespaceKeyAnalyzer;
use Ufo\DTO\Schema\Type\TypeCompatibility;
use Ufo\DTO\Schema\Type\TypeSchemaResolverInterface;

/**
 * Шар схем класів: один провайдер на обидва напрямки, без залежності від гідраторів.
 */
class DefaultSchemaFactory
{
    public function createClassSchemaProvider(
        ReflectionMetadataProviderInterface $metadataProvider,
        TypeSchemaResolverInterface $typeSchemaResolver,
        ?CacheInterface $persistentCache = null,
    ): ClassSchemaProviderInterface
    {
        $schemaFactory = $this->createClassSchemaFactory($metadataProvider, $typeSchemaResolver);
        $describer = $persistentCache === null
            ? $schemaFactory
            : new CachedClassSchemaDescriber($schemaFactory, $persistentCache);

        return new CachedClassSchemaProvider(new ClassSchemaProvider($describer, $schemaFactory));
    }

    public function createDefaultValueResolver(ReflectionMetadataProviderInterface $metadataProvider): DefaultValueResolver
    {
        return new DefaultValueResolver(new TypeCompatibility(), $metadataProvider);
    }

    protected function createClassSchemaFactory(
        ReflectionMetadataProviderInterface $metadataProvider,
        TypeSchemaResolverInterface $typeSchemaResolver,
    ): ClassSchemaFactory
    {
        return new ClassSchemaFactory(
            $metadataProvider,
            $typeSchemaResolver,
            $this->createDefaultValueResolver($metadataProvider),
            new NamespaceKeyAnalyzer($metadataProvider),
            new SerializationContextAttributeReader(),
        );
    }
}
