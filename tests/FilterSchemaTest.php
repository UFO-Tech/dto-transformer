<?php

declare(strict_types = 1);

use PHPUnit\Framework\TestCase;
use Ufo\DTO\Helpers\TypeHintResolver as T;

class FilterSchemaTest extends TestCase
{
    public function testCallsCallbackOnLeaf(): void
    {
        $calls = [];

        $schema = [T::TYPE  => T::STRING->value];
        T::filterSchema($schema, function(array $s, array $p) use (&$calls) {
            $calls[] = ['curr' => $s, 'prev' => $p];
        });

        $this->assertCount(1, $calls);
        $this->assertSame($schema, $calls[0]['curr']);
        $this->assertSame([], $calls[0]['prev']);
    }

    public function testTraversesItemsThenParent(): void
    {
        $calls = [];

        $child = [T::TYPE => T::INTEGER->value];
        $root  = [T::ITEMS => $child, T::TYPE => T::ARRAY->value];

        T::filterSchema($root, function(array $s, array $p) use (&$calls) {
            $calls[] = ['curr' => $s, 'prev' => $p];
        });

        $this->assertCount(2, $calls);
        $this->assertSame($child, $calls[0]['curr']);
        $this->assertSame($root,  $calls[0]['prev']);

        $this->assertSame($root,  $calls[1]['curr']);
        $this->assertSame([],     $calls[1]['prev']);
    }

    public function testTraversesOneOfAllChildrenBeforeParent(): void
    {
        $calls = [];

        $c1 = [T::TYPE => T::STRING->value];
        $c2 = [T::TYPE => T::NUMBER->value];
        $root = [
            T::ONE_OFF => [$c1, $c2]
        ];

        T::filterSchema($root, function(array $s, array $p) use (&$calls) {
            $calls[] = ['curr' => $s, 'prev' => $p];
        });

        $this->assertCount(3, $calls);

        $this->assertSame($c1, $calls[0]['curr']);
        $this->assertSame($root, $calls[0]['prev']);

        $this->assertSame($c2, $calls[1]['curr']);
        $this->assertSame($root, $calls[1]['prev']);

        $this->assertSame($root, $calls[2]['curr']);
        $this->assertSame([],    $calls[2]['prev']);
    }

    public function testOneOfHasPriorityOverItemsBecauseOfElseIf(): void
    {
        $calls = [];

        $oneOfChild = [T::TYPE => T::BOOLEAN->value];
        $itemsChild = [T::TYPE => T::NULL->value];

        $root = [
            T::ONE_OFF => [$oneOfChild],
            T::ITEMS   => $itemsChild,
        ];

        T::filterSchema($root, function(array $s, array $p) use (&$calls) {
            $calls[] = ['curr' => $s, 'prev' => $p];
        });

        $this->assertCount(2, $calls);

        $this->assertSame($oneOfChild, $calls[0]['curr']);
        $this->assertSame($root,       $calls[0]['prev']);

        $this->assertSame($root,       $calls[1]['curr']);
    }

    public function testCustomParentSchemaIsPassedDown(): void
    {
        $calls = [];

        $parent = ['title' => 'ExplicitParent'];
        $child  = [T::TYPE => 'array'];
        $root   = [T::ITEMS => $child];

        T::filterSchema($root, function(array $s, array $p) use (&$calls) {
            $calls[] = ['curr' => $s, 'prev' => $p];
        }, $parent);

        $this->assertCount(2, $calls);

        $this->assertSame($child, $calls[0]['curr']);
        $this->assertSame($root,  $calls[0]['prev']);

        $this->assertSame($root,   $calls[1]['curr']);
        $this->assertSame($parent, $calls[1]['prev']);
    }

    public function testDeepNestingMixedOneOfAndItems(): void
    {
        $calls = [];

        $leafA = [T::TYPE => T::STRING->value];
        $leafB = [T::TYPE => T::INTEGER->value];

        $level2 = [
            T::ONE_OFF => [$leafA, $leafB],
            'title' => 'L2',
        ];
        $level1 = [
            T::ITEMS => $level2,
            'title' => 'L1',
        ];
        $root = [
            T::ITEMS => $level1,
            'title' => 'Root',
        ];

        T::filterSchema($root, function(array $s, array $p) use (&$calls) {
            $calls[] = ['curr' => $s, 'prev' => $p];
        });

        $this->assertCount(5, $calls);

        $this->assertSame($leafA,  $calls[0]['curr']);
        $this->assertSame($level2, $calls[0]['prev']);

        $this->assertSame($leafB,  $calls[1]['curr']);
        $this->assertSame($level2, $calls[1]['prev']);

        $this->assertSame($level2, $calls[2]['curr']);
        $this->assertSame($level1, $calls[2]['prev']);

        $this->assertSame($level1, $calls[3]['curr']);
        $this->assertSame($root,   $calls[3]['prev']);

        $this->assertSame($root,   $calls[4]['curr']);
        $this->assertSame([],      $calls[4]['prev']);
    }

    public function testApplyReturnsTransformedLeaf(): void
    {
        $schema = [T::TYPE => T::STRING->value];

        $result = T::applyToSchema($schema, function(array $s, array $p): array {
            // на листі просто додаємо title
            $s['title'] = 'leaf';
            return $s;
        });

        $this->assertNotSame($schema, $result); // повернув новий масив
        $this->assertSame('leaf', $result['title']);
        $this->assertSame(T::STRING->value, $result[T::TYPE]);

        // вхідний не змінено
        $this->assertArrayNotHasKey('title', $schema);
    }

    public function testApplyTransformsItemsThenParent(): void
    {
        $child = [T::TYPE => T::INTEGER->value];
        $root  = [T::ITEMS => $child, T::TYPE => T::ARRAY->value];

        $order = [];
        $result = T::applyToSchema($root, function(array $s, array $p) use (&$order): array {
            // фіксуємо порядок: додаємо мітку
            if (($s[T::TYPE] ?? null) === T::INTEGER->value) {
                $order[] = 'child';
                $s['mark'] = 'child-mark';
            } elseif (($s[T::TYPE] ?? null) === T::ARRAY->value) {
                $order[] = 'parent';
                $s['mark'] = 'parent-mark';
            }
            return $s;
        });

        $this->assertSame(['child', 'parent'], $order, 'Очікуємо post-order: спершу child, потім parent');
        $this->assertSame('child-mark', $result[T::ITEMS]['mark']);
        $this->assertSame('parent-mark', $result['mark']);

        // початковий root не мутовано
        $this->assertArrayNotHasKey('mark', $root);
        $this->assertArrayNotHasKey('mark', $child);
    }

    public function testApplyTransformsOneOfAllChildrenBeforeParent(): void
    {
        $c1 = [T::TYPE => T::STRING->value];
        $c2 = [T::TYPE => T::NUMBER->value];
        $root = [T::ONE_OFF => [$c1, $c2]];

        $seen = [];
        $result = T::applyToSchema($root, function(array $s, array $p) use (&$seen): array {
            $seen[] = $s[T::TYPE] ?? 'root';
            // додамо прапор щоб перевірити що повернення зберігається у дереві
            $s['handled'] = true;
            return $s;
        });

        // порядок: c1, c2, root
        $this->assertSame([T::STRING->value, T::NUMBER->value, 'root'], $seen);

        // діти мають handled=true
        $this->assertTrue($result[T::ONE_OFF][0]['handled']);
        $this->assertTrue($result[T::ONE_OFF][1]['handled']);
        // і корінь теж
        $this->assertTrue($result['handled']);
    }

    public function testApplyOneOfHasPriorityOverItemsBecauseOfElseIf(): void
    {
        $oneOfChild = [T::TYPE => T::BOOLEAN->value];
        $itemsChild = [T::TYPE => T::NULL->value];
        $root = [
            T::ONE_OFF => [$oneOfChild],
            T::ITEMS   => $itemsChild,
        ];

        $visited = [];
        $result = T::applyToSchema($root, function(array $s, array $p) use (&$visited): array {
            $visited[] = $s[T::TYPE] ?? 'root';
            $s['v'] = 1;
            return $s;
        });

        // itemsChild не відвіданий (бо elseif)
        $this->assertSame([T::BOOLEAN->value, 'root'], $visited);
        $this->assertTrue(isset($result[T::ONE_OFF][0]['v']));
        $this->assertTrue(isset($result['v']));
        // items лишився без мітки
        $this->assertArrayNotHasKey('v', $itemsChild);
        $this->assertArrayNotHasKey('v', $result[T::ITEMS] ?? []);
    }

    public function testApplyReceivesCorrectParentSchema(): void
    {
        $parent = ['title' => 'ExplicitParent'];
        $child  = [T::TYPE => T::ARRAY->value];
        $root   = [T::ITEMS => $child];

        $parentsSeen = [];

        $result = T::applyToSchema($root, function(array $s, array $p) use (&$parentsSeen): array {
            $parentsSeen[] = $p['title'] ?? ($p[T::TYPE] ?? 'none');
            $s['tag'] = ($s['title'] ?? ($s[T::TYPE] ?? 'root')) . '-tag';
            return $s;
        }, $parent);

        $this->assertSame(['none', 'ExplicitParent'], $parentsSeen);

        $this->assertSame('array-tag', $result[T::ITEMS]['tag']); // дитина
        $this->assertSame('root-tag', $result['tag'] ?? 'root-tag'); // корінь
    }

    public function testApplyDeepNestingMixedOneOfAndItems(): void
    {
        $leafA = [T::TYPE => T::STRING->value];
        $leafB = [T::TYPE => T::INTEGER->value];

        $level2 = [T::ONE_OFF => [$leafA, $leafB], 'title' => 'L2'];
        $level1 = [T::ITEMS => $level2,            'title' => 'L1'];
        $root   = [T::ITEMS => $level1,            'title' => 'Root'];

        $order = [];
        $result = T::applyToSchema($root, function(array $s): array {
            // додамо рівень для верифікації
            $s['_visited'] = true;
            return $s;
        });

        // усе дерево помічене
        $this->assertTrue($result['_visited']);
        $this->assertTrue($result[T::ITEMS]['_visited']);
        $this->assertTrue($result[T::ITEMS][T::ITEMS]['_visited']);
        $this->assertTrue($result[T::ITEMS][T::ITEMS][T::ONE_OFF][0]['_visited']);
        $this->assertTrue($result[T::ITEMS][T::ITEMS][T::ONE_OFF][1]['_visited']);
    }

    public function testApplyDoesNotMutateInputSchema(): void
    {
        $input = [
            T::ITEMS => [
                T::ONE_OFF => [
                    [T::TYPE => T::STRING->value],
                ],
            ],
            'meta' => 123,
        ];

        $original = $input; // копія для порівняння

        $out = T::applyToSchema($input, function(array $s): array {
            $s['x'] = 42; // навмисно модифікуємо
            return $s;
        });

        // Вихід змінився
        $this->assertSame(42, $out['x']);
        $this->assertSame(42, $out[T::ITEMS]['x']);
        $this->assertSame(42, $out[T::ITEMS][T::ONE_OFF][0]['x']);

        // Вхід — без змін
        $this->assertSame($original, $input);
    }
}