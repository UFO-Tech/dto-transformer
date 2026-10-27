<?php

declare(strict_types=1);

namespace Ufo\DTO\Helpers;

use function implode;

/**
 * Один екземпляр масиву на однаковий список рядків у пулі викликача; пул обмежено, як {@see BoundedArray}.
 */
final class StringListPool
{
    /**
     * @param array<string, list<string>> $lists
     * @param list<string> $list
     * @return list<string>
     */
    public static function share(array &$lists, array $list, int $limit): array
    {
        $key = implode("\0", $list);

        return $lists[$key] ?? BoundedArray::put($lists, $key, $list, $limit);
    }
}
