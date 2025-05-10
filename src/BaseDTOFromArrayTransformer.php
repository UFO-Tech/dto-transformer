<?php

namespace Ufo\DTO;


use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Exceptions\NotSupportDTOException;
use Ufo\DTO\Interfaces\IDTOFromArrayTransformer;

abstract class BaseDTOFromArrayTransformer implements IDTOFromArrayTransformer
{
    /**
     * Creates a DTO object from an associative array.
     *
     * @param string $classFQCN
     * @param array $data
     * @param array<string,string|null> $renameKey
     * @return object
     * @throws BadParamException
     * @throws NotSupportDTOException
     */
    public static function fromArray(
        string $classFQCN, 
        array $data,
        array $renameKey = []
    ): object
    {
        if (!static::isSupportClass($classFQCN)) {
            throw new NotSupportDTOException(static::class . ' is not support transform for ' . $classFQCN);
        }
        try {
            return static::transformFromArray($classFQCN, $data, $renameKey);
        } catch (\Throwable $e) {
            throw new BadParamException($e->getMessage(), $e->getCode(), $e);
        }
    }

    abstract protected static function transformFromArray(string $classFQCN, array $data, array $renameKey = []): object;

}