<?php

namespace Ufo\DTO\Interfaces;

interface IArrayConstructible
{
    public static function fromArray(array $data, array $renameKey = []): static;
}