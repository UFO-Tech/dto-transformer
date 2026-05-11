<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Metadata;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Ufo\DTO\Attributes\StrictMode;
use Ufo\DTO\Interfaces\Meta\StrictModeResolverInterface;
use Ufo\DTO\Tests\Support\TransformerFactory;

final class StrictModeResolverTest extends TestCase
{
    private StrictModeResolverInterface $resolver;

    protected function setUp(): void
    {
        $this->resolver = TransformerFactory::strictModeResolver();
    }

    public function testPropertyAttributeWinsOverClassMetadata(): void
    {
        $fixture = new #[StrictMode(false)] class {
            #[StrictMode(true)]
            public array $items;
        };

        $this->assertTrue($this->resolver->resolve(new ReflectionProperty($fixture, 'items')));
    }

    public function testParameterAttributeWinsOverMethodMetadata(): void
    {
        $fixture = new class {
            #[StrictMode(false)]
            public function handle(
                #[StrictMode(true)]
                array $items,
            ): void {}
        };

        $parameter = (new ReflectionMethod($fixture, 'handle'))->getParameters()[0];

        $this->assertTrue($this->resolver->resolve($parameter));
    }

    public function testMethodAttributeAppliesToParameter(): void
    {
        $fixture = new class {
            #[StrictMode(true)]
            public function handle(array $items): void {}
        };

        $parameter = (new ReflectionMethod($fixture, 'handle'))->getParameters()[0];

        $this->assertTrue($this->resolver->resolve($parameter));
    }

    public function testDocblockParameterMetadataAppliesToMatchingParameter(): void
    {
        $fixture = new class {
            /**
             * @strictMode items true
             * @strictMode ignored false
             */
            public function handle(array $items, array $ignored): void {}
        };

        $method = new ReflectionMethod($fixture, 'handle');

        $this->assertTrue($this->resolver->resolve($method->getParameters()[0]));
        $this->assertFalse($this->resolver->resolve($method->getParameters()[1]));
    }

    public function testClassAttributeIsInheritedByProperty(): void
    {
        $fixture = new class extends StrictModeParentFixture {
            public array $items;
        };

        $this->assertTrue($this->resolver->resolve(new ReflectionProperty($fixture, 'items')));
    }

    public function testInterfaceDocblockMetadataIsInheritedByProperty(): void
    {
        $fixture = new class implements StrictModeInterfaceFixture {
            public array $items;
        };

        $this->assertTrue($this->resolver->resolve(new ReflectionProperty($fixture, 'items')));
    }

    public function testDefaultValueIsUsedWhenNoMetadataExists(): void
    {
        $fixture = new class {
            public array $items;
        };

        $this->assertFalse($this->resolver->resolve(new ReflectionProperty($fixture, 'items')));
        $this->assertTrue($this->resolver->resolve(new ReflectionProperty($fixture, 'items'), true));
    }

    public function testClassDocblockCanDisableInheritedDefault(): void
    {
        $reflection = new ReflectionClass(StrictModeDisabledDocblockFixture::class);

        $this->assertFalse($this->resolver->resolve($reflection->getProperty('items'), true));
    }
}
