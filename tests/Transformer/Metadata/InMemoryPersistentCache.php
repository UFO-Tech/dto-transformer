<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Metadata;

use Symfony\Contracts\Cache\CacheInterface;

final class InMemoryPersistentCache implements CacheInterface
{
    public int $getCalls = 0;

    /**
     * @var string[]
     */
    public array $deletedKeys = [];

    /**
     * @param array<string, mixed> $values
     */
    public function __construct(
        public array $values = [],
    ) {
    }

    public function get(string $key, callable $callback, ?float $beta = null, ?array &$metadata = null): mixed
    {
        $this->getCalls++;

        if (array_key_exists($key, $this->values)) {
            return $this->values[$key];
        }

        return $this->values[$key] = $callback();
    }

    public function delete(string $key): bool
    {
        $this->deletedKeys[] = $key;
        unset($this->values[$key]);

        return true;
    }
}
