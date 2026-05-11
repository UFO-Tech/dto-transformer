<?php

declare(strict_types=1);

namespace Ufo\DTO\Interfaces\Converter;

interface DateTimeValueConverterInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function toScalar(object $object, array $context = [], ?callable $callback = null): string|int|float|null;

    /**
     * @param array<string, mixed> $context
     */
    public function toObject(int|string|float|null $value, array $context = [], ?callable $callback = null): ?object;

    public function supported(string $classFQCN): bool;
}

