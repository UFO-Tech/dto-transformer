<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks;

final class Format
{
    public static function duration(float $milliseconds): string
    {
        return $milliseconds < 1.0
            ? self::number($milliseconds * 1000, 2) . ' µs'
            : self::number($milliseconds, 2) . ' ms';
    }

    public static function bytes(int|float $bytes): string
    {
        return match (true) {
            abs($bytes) < 1024 => self::number($bytes, 0) . ' B',
            abs($bytes) < 1048576 => self::number($bytes / 1024, 1) . ' KB',
            default => self::number($bytes / 1048576, 2) . ' MB',
        };
    }

    public static function number(int|float $value, int $decimals): string
    {
        return number_format($value, $decimals, '.', ' ');
    }

    public static function percent(float $ratio): string
    {
        return self::number($ratio * 100, 1) . '%';
    }

    public static function width(string $text): int
    {
        return function_exists('mb_strwidth') ? mb_strwidth($text, 'UTF-8') : (int) preg_match_all('/./us', $text);
    }

    public static function padLeft(string $text, int $width): string
    {
        return str_repeat(' ', max(0, $width - self::width($text))) . $text;
    }
}
