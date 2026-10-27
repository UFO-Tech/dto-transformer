<?php

declare(strict_types=1);

namespace Ufo\DTO\Helpers;

use function array_key_exists;
use function array_key_first;
use function count;

/**
 * Масив-кеш з лімітом записів: при переповненні витісняється найстаріший запис.
 */
final class BoundedArray
{
    /**
     * @template T
     * @param array<array-key, T> $items
     * @param T $value
     * @return T
     */
    public static function put(array &$items, string|int $key, mixed $value, int $limit): mixed
    {
        if (!array_key_exists($key, $items) && count($items) >= $limit) {
            unset($items[array_key_first($items)]);
        }

        return $items[$key] = $value;
    }
}
