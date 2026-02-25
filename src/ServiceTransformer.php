<?php

namespace Ufo\DTO;

use ReflectionException;

class ServiceTransformer extends DTOTransformer
{
    /**
     * @param string $classFQCN
     * @param array $data
     * @param array $renameKey
     * @param array $namespaces
     * @return object
     * @throws ReflectionException
     */
    public static function fromArray(string $classFQCN, array $data, array $renameKey = [], array $namespaces = []): object
    {
        $classFQCN = $data['$classFQCN'] ?? $classFQCN;
        return static::transformFromArray($classFQCN, $data, $renameKey, $namespaces);
    }

    public static function isSupportClass(string $classFQCN): bool
    {
        return true;
    }

    public static function toArray(object $dto, array $renameKey = [], bool $asSmartArray = true, bool $publicOnly = true): array
    {
        $array = parent::toArray($dto, $renameKey, $asSmartArray, $publicOnly);
        $array['$classFQCN'] = $dto::class;
        return $array;
    }

}