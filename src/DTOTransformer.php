<?php

namespace Ufo\DTO;

use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Exceptions\NotInitializeException;
use Ufo\DTO\Interfaces\DTOFromArrayTransformerInterface;
use Ufo\DTO\Interfaces\DTOToArrayTransformerInterface;
use Ufo\DTO\Interfaces\IDTOFromArrayTransformer;
use Ufo\DTO\Interfaces\IDTOToArrayTransformer;

use function class_exists;

class DTOTransformer extends BaseDTOFromArrayTransformer implements IDTOToArrayTransformer, IDTOFromArrayTransformer
{
    /**
     * @var array<class-string<self>, self>
     */
    private static array $instances = [];

    public function __construct(
        protected DTOFromArrayTransformerInterface $fromArrayTransformer,
        protected DTOToArrayTransformerInterface $toArrayTransformer,
    ) {}

    public static function boot(self $transformer): void
    {
        if (isset(self::$instances[static::class]))
            throw new \RuntimeException('DTOTransformer is already initialized');
        self::$instances[static::class] = $transformer;
    }

    public static function reset(): void
    {
        unset(self::$instances[static::class]);
    }

    public static function isInitialized(): bool
    {
        return isset(self::$instances[static::class]);
    }

    protected static function getInstance(?string $class = null): static
    {
        return self::$instances[$class ?? static::class] ?? throw new NotInitializeException();
    }

    public static function toArray(
        object $dto,
        array $renameKey = [],
        bool $asSmartArray = false,
        bool $publicOnly = true,
        array $context = [],
    ): array
    {
        $self = static::getInstance();
        return $self->transformToArray($dto, $renameKey, $asSmartArray, $publicOnly, $context);
    }

    /**
     * Converts a DTO object to an associative array.
     *
     * @param object $dto The object to convert.
     * @param array<string,string|null> $renameKey
     *
     * @return array An associative array of the object's properties.
     */
    public function transformToArray(
        object $dto,
        array $renameKey = [],
        bool $asSmartArray = false,
        bool $publicOnly = true,
        array $context = [],
    ): array
    {
        return $this->toArrayTransformer->transformToArray($dto, $renameKey, $asSmartArray, $publicOnly, $context);
    }

    /**
     * @throws BadParamException
     */
    public static function transformFromArray(
        string $classFQCN,
        array $data,
        array $renameKey = [],
        array $namespaces = [],
        array $context = [],
    ): object
    {
        $self = static::getInstance();
        return $self->fromArrayTransformer->transformFromArray($classFQCN, $data, $renameKey, $namespaces, $context);
    }

    public static function isSupportClass(string $classFQCN): bool
    {
        return class_exists($classFQCN);
    }
}
