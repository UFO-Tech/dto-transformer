<?php

namespace Ufo\DTO;


use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Exceptions\NotSupportDTOException;
use Ufo\DTO\Interfaces\IDTOFromArrayTransformer;

abstract class BaseDTOFromArrayTransformer implements IDTOFromArrayTransformer
{
    const string DTO_CLASSNAME = '$className';

    /**
     *  default namespace for DTO
     */
    const string DTO_NS_KEY = '$defaultNamespace';

    /**
     * Creates a DTO object from an associative array.
     *
     * @param string $classFQCN
     * @param array $data
     * @param array<string,string|null> $renameKey
     * @param array<string, string> $namespaces
     * @return object
     */
    public static function fromArray(
        string $classFQCN, 
        array $data,
        array $renameKey = [],
        array $namespaces = []
    ): object
    {
        if (!static::isSupportClass($classFQCN)) {
            throw new NotSupportDTOException(static::class . ' is not support transform for ' . $classFQCN);
        }
        try {
            try {
                return static::fromSmartArray($data, $renameKey, namespaces: $namespaces);
            } catch (NotSupportDTOException) {
                return static::transformFromArray($classFQCN, $data, $renameKey, namespaces: $namespaces);
            }
        } catch (\Throwable $e) {
            throw new BadParamException($e->getMessage(), $e->getCode(), $e);
        }
    }

    abstract protected static function transformFromArray(string $classFQCN, array $data, array $renameKey = [], array $namespaces = []): object;

}