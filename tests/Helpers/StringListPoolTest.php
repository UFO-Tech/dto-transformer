<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Helpers;

use PHPUnit\Framework\TestCase;
use Ufo\DTO\Helpers\StringListPool;

final class StringListPoolTest extends TestCase
{
    public function testEqualListsShareOneStoredList(): void
    {
        $lists = [];

        $first = StringListPool::share($lists, ['$defaultNamespace', 'Item'], 10);
        StringListPool::share($lists, ['$defaultNamespace', 'Item'], 10);
        StringListPool::share($lists, ['Other'], 10);

        $this->assertSame(['$defaultNamespace', 'Item'], $first);
        $this->assertCount(2, $lists);
    }

    public function testListsWithSameItemsInOtherOrderStaySeparate(): void
    {
        $lists = [];

        $this->assertSame(['a', 'b'], StringListPool::share($lists, ['a', 'b'], 10));
        $this->assertSame(['b', 'a'], StringListPool::share($lists, ['b', 'a'], 10));
        $this->assertCount(2, $lists);
    }

    public function testPoolIsBounded(): void
    {
        $lists = [];

        for ($i = 0; $i < 10; $i++) {
            StringListPool::share($lists, ['Ns' . $i], 3);
        }

        $this->assertCount(3, $lists);
    }
}
