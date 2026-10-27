<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Context;

use PHPUnit\Framework\TestCase;
use Ufo\DTO\Transformer\Context\TransformationContext;

final class TransformationContextTest extends TestCase
{
    public function testWithStrictReturnsSameInstanceWhenUnchanged(): void
    {
        $context = new TransformationContext(strict: true);

        $this->assertSame($context, $context->withStrict(true));
        $this->assertFalse($context->withStrict(false)->isStrict());
    }

    public function testWithNamespacesReturnsSameInstanceWhenUnchanged(): void
    {
        $context = new TransformationContext(['Dto' => 'App\\Dto']);

        $this->assertSame($context, $context->withNamespaces([]));
        $this->assertSame($context, $context->withNamespaces(['Dto' => 'App\\Dto']));
        $this->assertSame(
            ['Dto' => 'App\\Dto', 'Item' => 'App\\Item'],
            $context->withNamespaces(['Item' => 'App\\Item'])->namespaces(),
        );
        $this->assertSame(['Dto' => 'Other\\Dto'], $context->withNamespaces(['Dto' => 'Other\\Dto'])->namespaces());
    }

    public function testWithContextReturnsSameInstanceOnlyWhenBothContextsAreEmpty(): void
    {
        $empty = new TransformationContext(['Dto' => 'App\\Dto'], true);
        $filled = new TransformationContext(['Dto' => 'App\\Dto'], true, ['custom' => 'value']);

        $this->assertSame($empty, $empty->withContext([]));
        $this->assertNull($filled->withContext([])->getFromContext('custom'));
        $this->assertSame('other', $empty->withContext(['custom' => 'other'])->getFromContext('custom'));
    }

    public function testArrayRoundTripKeepsContext(): void
    {
        $context = new TransformationContext(['Dto' => 'App\\Dto'], true, ['custom' => 'value']);

        $restored = TransformationContext::fromArray($context->toArray(), namespaces: $context->namespaces());

        $this->assertEquals($context, $restored);
    }
}
