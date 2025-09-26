<?php

declare(strict_types = 1);

namespace Ufo\DTO;

use JsonException;

trait JsonSerializableTrait
{
    abstract public function toArray(): array;

    /**
     * @throws JsonException
     */
    public function jsonSerialize(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}