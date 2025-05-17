<?php

namespace Ufo\DTO;

use ReflectionException;

class ServiceTransformer extends DTOTransformer
{
    /**
     * @throws ReflectionException
     */
    public static function fromArray(string $classFQCN, array $data, array $renameKey = []): object
    {
        $classFQCN = $data['$classFQCN'] ?? $classFQCN;
        return static::transformFromArray($classFQCN, $data, $renameKey);
    }

    public static function isSupportClass(string $classFQCN): bool
    {
        return true;
    }

    public static function toArray(object $dto, array $renameKey = []): array
    {
        $array = parent::toArray($dto, $renameKey);
        $array['$classFQCN'] = $dto::class;
        return $array;
    }

}