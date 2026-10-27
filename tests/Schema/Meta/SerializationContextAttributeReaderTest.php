<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Schema\Meta;

use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Ufo\DTO\Tests\Fixtures\DTO\SerializationContextDTO;
use Ufo\DTO\Transformer\Context\NormalizationContext;
use Ufo\DTO\Schema\Meta\SerializationContextAttributeReader;

final class SerializationContextAttributeReaderTest extends TestCase
{
    public function testItReturnsNoValuesWhenPropertyHasNoSerializationContext(): void
    {
        $reader = new SerializationContextAttributeReader();

        $this->assertSame([], $reader->read(new ReflectionProperty(SerializationContextDTO::class, 'status')));
    }

    public function testItReturnsSerializationContextValuesOnEveryCall(): void
    {
        $reader = new SerializationContextAttributeReader();
        $property = new ReflectionProperty(SerializationContextDTO::class, 'createdAt');

        $this->assertSame(DateTimeInterface::ATOM, $reader->read($property)[NormalizationContext::DATE_FORMAT]);
        $this->assertSame(DateTimeInterface::ATOM, $reader->read($property)[NormalizationContext::DATE_FORMAT]);
    }
}
