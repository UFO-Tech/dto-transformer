<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks;

/**
 * Знімок CPU (getrusage) та I/O процесу (/proc/self/io, лише Linux).
 */
final readonly class ResourceUsage
{
    private const string PROC_IO = '/proc/self/io';

    /**
     * @param array<string, int>|null $io rchar, wchar, read_bytes, write_bytes, syscr, syscw
     */
    public function __construct(
        public float $cpuMs,
        public int $voluntarySwitches,
        public int $involuntarySwitches,
        public ?array $io,
    ) {}

    public static function now(): self
    {
        $usage = getrusage();

        return new self(
            cpuMs: ($usage['ru_utime.tv_sec'] + $usage['ru_stime.tv_sec']) * 1000
                + ($usage['ru_utime.tv_usec'] + $usage['ru_stime.tv_usec']) / 1000,
            voluntarySwitches: (int) $usage['ru_nvcsw'],
            involuntarySwitches: (int) $usage['ru_nivcsw'],
            io: self::io(),
        );
    }

    public function minus(self $before): self
    {
        return $this->combine($before, -1);
    }

    public function plus(self $other): self
    {
        return $this->combine($other, 1);
    }

    public function ioOnly(): self
    {
        return new self(0.0, 0, 0, $this->io);
    }

    public static function zero(): self
    {
        return new self(0.0, 0, 0, is_readable(self::PROC_IO) ? array_fill_keys(self::ioKeys(), 0) : null);
    }

    private function combine(self $other, int $sign): self
    {
        $io = null;
        if ($this->io !== null && $other->io !== null) {
            $io = [];
            foreach ($this->io as $key => $value) {
                $io[$key] = $value + $sign * ($other->io[$key] ?? 0);
            }
        }

        return new self(
            cpuMs: $this->cpuMs + $sign * $other->cpuMs,
            voluntarySwitches: $this->voluntarySwitches + $sign * $other->voluntarySwitches,
            involuntarySwitches: $this->involuntarySwitches + $sign * $other->involuntarySwitches,
            io: $io,
        );
    }

    /**
     * @return array<string, int>|null
     */
    private static function io(): ?array
    {
        $content = @file_get_contents(self::PROC_IO);
        if ($content === false) {
            return null;
        }

        $values = [];
        foreach (explode("\n", trim($content)) as $line) {
            [$key, $value] = array_map('trim', explode(':', $line, 2)) + [1 => '0'];
            $values[$key] = (int) $value;
        }

        $io = [];
        foreach (self::ioKeys() as $key) {
            $io[$key] = $values[$key] ?? 0;
        }

        return $io;
    }

    /**
     * @return list<string>
     */
    private static function ioKeys(): array
    {
        return ['rchar', 'wchar', 'read_bytes', 'write_bytes', 'syscr', 'syscw'];
    }
}
