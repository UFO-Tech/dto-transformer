<?php

namespace Ufo\DTO;

use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Exceptions\NotInitializeException;
use Ufo\DTO\Exceptions\NotSupportDTOException;
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
    ): object
    {
        $self = static::getInstance();
        return $self->fromArrayTransformer->transformFromArray($classFQCN, $data, $renameKey, $namespaces);
    }

    public static function isSupportClass(string $classFQCN): bool
    {
        return class_exists($classFQCN);
    }

    public static function fromSmartArray(array $data, array $renameKey = [], array $namespaces = []): object
    {
        $classFQCN = $data[static::DTO_CLASSNAME] ?? throw new NotSupportDTOException('Missing class name');
        if (!class_exists($classFQCN)) {
            $namespace = $namespaces[$classFQCN] ?? $namespaces[static::DTO_NS_KEY]
                ?? throw new NotSupportDTOException('Namespace not found for class: ' . $classFQCN);
            $classFQCN = $namespace . '\\' . $classFQCN;
        }
        if (!class_exists($classFQCN)) throw new NotSupportDTOException('Class not exist: ' . $classFQCN);

        unset($data[static::DTO_CLASSNAME]);
        return static::fromArray($classFQCN, $data, $renameKey, namespaces: $namespaces);
    }

    public function support(string $classFQCN): bool
    {
        return class_exists($classFQCN);
    }
}
