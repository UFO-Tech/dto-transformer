<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks\Fixture;

/**
 * Згенеровані DTO для сценаріїв pressure і growth: робочий набір, більший за ліміти кешів.
 */
final class GeneratedClasses
{
    public const string NAMESPACE = 'Ufo\\DTO\\Benchmarks\\Generated';

    private const int PROPERTIES = 12;

    private const array TYPES = ['string', 'int', '?string', 'float', 'bool'];

    private static int $generated = 0;

    /**
     * @return list<class-string>
     */
    public static function ensure(int $count): array
    {
        for ($index = self::$generated; $index < $count; $index++) {
            eval(self::source($index));
        }
        self::$generated = max(self::$generated, $count);

        return array_map(self::className(...), range(0, $count - 1));
    }

    /**
     * @return class-string
     */
    public static function className(int $index): string
    {
        return self::NAMESPACE . '\\Dto' . $index;
    }

    /**
     * @return array<string, mixed>
     */
    public static function payload(int $index): array
    {
        $payload = [];
        for ($property = 0; $property < self::PROPERTIES; $property++) {
            $payload['p' . $property] = match ($property % count(self::TYPES)) {
                0 => 'value-' . $index,
                1 => $index,
                2 => null,
                3 => $index + 0.5,
                default => $index % 2 === 0,
            };
        }

        return $payload;
    }

    private static function source(int $index): string
    {
        $parameters = [];
        for ($property = 0; $property < self::PROPERTIES; $property++) {
            $parameters[] = sprintf('public %s $p%d', self::TYPES[$property % count(self::TYPES)], $property);
        }

        return sprintf(
            'namespace %s; final class Dto%d { public function __construct(%s) {} }',
            self::NAMESPACE,
            $index,
            implode(', ', $parameters),
        );
    }
}
