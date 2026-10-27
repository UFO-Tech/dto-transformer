<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks\Fixture;

use Symfony\Contracts\Cache\CacheInterface;

/**
 * Persistent-кеш для режиму cold-persistent без залежності від symfony/cache.
 */
final readonly class FilesystemCache implements CacheInterface
{
    public function __construct(
        private string $directory,
    ) {
        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0777, true);
        }
    }

    public function get(string $key, callable $callback, ?float $beta = null, ?array &$metadata = null): mixed
    {
        $file = $this->file($key);
        if (is_file($file)) {
            return unserialize((string) file_get_contents($file));
        }

        $save = true;
        $value = $callback(null, $save);
        if ($save) {
            file_put_contents($file, serialize($value), LOCK_EX);
        }

        return $value;
    }

    public function delete(string $key): bool
    {
        $file = $this->file($key);

        return !is_file($file) || unlink($file);
    }

    public static function removeDirectory(string $directory): void
    {
        foreach (glob($directory . '/*') ?: [] as $file) {
            is_dir($file) ? self::removeDirectory($file) : unlink($file);
        }

        if (is_dir($directory)) {
            rmdir($directory);
        }
    }

    private function file(string $key): string
    {
        return $this->directory . '/' . md5($key);
    }
}
