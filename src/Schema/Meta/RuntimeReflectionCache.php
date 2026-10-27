<?php

declare(strict_types=1);

namespace Ufo\DTO\Schema\Meta;

use Symfony\Contracts\Cache\CacheInterface;

use function array_key_exists;
use function array_key_first;
use function count;
use function md5;

class RuntimeReflectionCache
{
    /** 0 — без ліміту; LRU-витіснення працює лише з додатним лімітом. */
    public const int DEFAULT_LIMIT = 2048;

    /**
     * @var array<string, mixed>
     */
    protected array $items = [];

    public function __construct(
        protected int $limit = self::DEFAULT_LIMIT,
        protected ?CacheInterface $persistentCache = null,
    ) {}

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->items);
    }

    public function get(string $key): mixed
    {
        if (!$this->has($key)) {
            return null;
        }

        $value = $this->items[$key];

        if ($this->limit > 0) {
            unset($this->items[$key]);
            $this->items[$key] = $value;
        }

        return $value;
    }

    public function set(string $key, mixed $value): mixed
    {
        $this->items[$key] = $value;

        $this->enforceLimit();

        return $value;
    }

    public function remember(string $key, callable $factory): mixed
    {
        if (array_key_exists($key, $this->items)) {
            return $this->limit > 0 ? $this->get($key) : $this->items[$key];
        }

        return $this->set($key, $factory());
    }

    public function rememberPersistent(
        string $key,
        callable $factory,
        ?callable $runtimeFactory = null,
    ): mixed
    {
        if (array_key_exists($key, $this->items)) {
            return $this->limit > 0 ? $this->get($key) : $this->items[$key];
        }

        if ($this->persistentCache === null) {
            $value = $factory();

            return $this->set(
                $key,
                $runtimeFactory === null ? $value : $runtimeFactory($value),
            );
        }

        $persistentKey = $this->persistentKey($key);

        $value = $this->persistentCache->get(
            $persistentKey,
            static fn (...$_): mixed => $factory(),
        );

        return $this->set(
            $key,
            $runtimeFactory === null ? $value : $runtimeFactory($value),
        );
    }

    public function delete(string $key): void
    {
        unset($this->items[$key]);

        $this->persistentCache?->delete(
            $this->persistentKey($key),
        );
    }

    public function clear(): void
    {
        $this->items = [];
    }

    protected function enforceLimit(): void
    {
        if ($this->limit <= 0) {
            return;
        }

        while (count($this->items) > $this->limit) {
            unset($this->items[array_key_first($this->items)]);
        }
    }

    protected function persistentKey(string $key): string
    {
        return md5($key);
    }
}
