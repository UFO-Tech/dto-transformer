<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Context;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;
use Ufo\DTO\Transformer\Context\NormalizationContext;

final class NormalizationContextTest extends TestCase
{
    public function testForPropertyValueBuildsNextLevelContextInOneStep(): void
    {
        $context = NormalizationContext::create(renameKey: ['name' => 'title'], asSmartArray: true, publicOnly: false, values: ['custom' => 1, 'format' => 'a']);
        $property = new ReflectionProperty(DummyDTO::class, 'name');
        $class = new ReflectionClass(DummyDTO::class);

        $result = $context->forPropertyValue($property, $class, ['format' => 'b']);

        $this->assertSame(['name' => 'title'], $result->renameKey());
        $this->assertTrue($result->asSmartArray());
        $this->assertFalse($result->publicOnly());
        $this->assertSame($property, $result->property());
        $this->assertSame($class, $result->reflectionClass());
        $this->assertSame($context->depth() + 1, $result->depth());
        $this->assertSame(1, $result->get('custom'));
        $this->assertSame('b', $result->get('format'));
    }
}
