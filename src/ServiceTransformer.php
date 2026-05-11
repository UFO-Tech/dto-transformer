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
        $self = static::getInstance();
        return $self->transformFromArray($classFQCN, $data, $renameKey, $namespaces);
    }

    public static function isSupportClass(string $classFQCN): bool
    {
        return true;
    }

    public static function toArray(object $dto, array $renameKey = [], bool $asSmartArray = true, bool $publicOnly = true, array $context = []): array
    {
        $self = static::getInstance();
        $self->transformToArray($dto, $renameKey, $asSmartArray, $publicOnly);
        $array['$classFQCN'] = $dto::class;
        return $array;
    }

}