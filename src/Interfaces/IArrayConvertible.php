<?php

namespace Ufo\DTO\Interfaces;

interface IArrayConvertible
{
    public function toArray(bool $publicOnly = true): array;
}