<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks;

final class Environment
{
    /**
     * @return array<string, string>
     */
    public static function capture(string $mode, string $projectDir): array
    {
        $opcache = extension_loaded('Zend OPcache') && filter_var(ini_get('opcache.enable_cli'), FILTER_VALIDATE_BOOL);

        return [
            'date' => gmdate('Y-m-d\TH:i:s\Z'),
            'mode' => $mode,
            'php' => PHP_VERSION,
            'sapi' => PHP_SAPI,
            'os' => PHP_OS_FAMILY . ' ' . php_uname('m'),
            'cpus' => self::cpuCount(),
            'cpu_model' => self::cpuModel(),
            'container' => is_file('/.dockerenv') ? 'docker' : 'not detected',
            'filesystem' => self::filesystem($projectDir),
            'temp_filesystem' => self::filesystem(sys_get_temp_dir()),
            'opcache' => $opcache ? 'on' : 'off',
            'jit' => $opcache ? ((string) ini_get('opcache.jit') ?: 'off') : 'off',
            'xdebug' => self::xdebugMode(),
            'memory_limit' => (string) ini_get('memory_limit'),
            'commit' => self::git($projectDir, ['rev-parse', '--short', 'HEAD']) ?? 'unknown',
            'dirty' => match (self::git($projectDir, ['status', '--porcelain'])) {
                null => 'unknown',
                '' => 'no',
                default => 'yes',
            },
        ];
    }

    private static function cpuCount(): string
    {
        $cpuinfo = @file_get_contents('/proc/cpuinfo');

        return $cpuinfo === false ? 'unknown' : (string) preg_match_all('/^processor\s*:/m', $cpuinfo);
    }

    private static function cpuModel(): string
    {
        $cpuinfo = (string) @file_get_contents('/proc/cpuinfo');

        return preg_match('/^(?:model name|Hardware)\s*:\s*(.+)$/m', $cpuinfo, $match) === 1
            ? trim($match[1])
            : 'not exposed';
    }

    private static function filesystem(string $path): string
    {
        $mounts = @file('/proc/self/mounts', FILE_IGNORE_NEW_LINES);
        if ($mounts === false) {
            return 'unknown';
        }

        $path = (string) realpath($path);
        $best = ['', 'unknown'];
        foreach ($mounts as $line) {
            [, $mountPoint, $type] = explode(' ', $line) + [1 => '', 2 => ''];
            $prefix = rtrim($mountPoint, '/') . '/';
            if (($path === $mountPoint || str_starts_with($path . '/', $prefix)) && strlen($mountPoint) >= strlen($best[0])) {
                $best = [$mountPoint, $type];
            }
        }

        return $best[1];
    }

    public static function xdebugMode(): string
    {
        if (!extension_loaded('xdebug')) {
            return 'not loaded';
        }

        $mode = getenv('XDEBUG_MODE');

        return $mode !== false ? $mode : (string) ini_get('xdebug.mode');
    }

    public static function xdebugActive(): bool
    {
        return !in_array(self::xdebugMode(), ['not loaded', 'off'], true);
    }

    /**
     * @param list<string> $arguments
     */
    private static function git(string $projectDir, array $arguments): ?string
    {
        if (!function_exists('proc_open')) {
            return null;
        }

        $process = @proc_open(
            ['git', '-C', $projectDir, ...$arguments],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );
        if (!is_resource($process)) {
            return null;
        }

        $output = trim((string) stream_get_contents($pipes[1]));
        fclose($pipes[1]);
        fclose($pipes[2]);

        return proc_close($process) === 0 ? $output : null;
    }
}
