<?php

declare(strict_types=1);

namespace Ufo\DTO\Interfaces\Meta;

interface RuntimeReflectionCacheInterface
{
    public function has(string $key): bool;

    public function get(string $key): mixed;

    public function set(string $key, mixed $value): mixed;

    public function remember(string $key, callable $factory): mixed;

    public function rememberPersistent(string $key, callable $factory, ?callable $runtimeFactory = null): mixed;

    public function delete(string $key): void;

    public function clear(): void;
}
