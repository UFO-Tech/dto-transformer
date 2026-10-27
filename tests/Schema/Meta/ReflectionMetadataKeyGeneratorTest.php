<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Schema\Meta;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use Ufo\DTO\Schema\Meta\ReflectionMetadataKeyGenerator;
use Ufo\DTO\Tests\Fixtures\DTO\UserDto;

final class ReflectionMetadataKeyGeneratorTest extends TestCase
{
    public function testReflectionKeyPrefixesEveryReflectionKind(): void
    {
        $generator = new ReflectionMetadataKeyGenerator();
        $function = new ReflectionFunction(static fn (int $value): int => $value);

        $this->assertSame('class.' . UserDto::class, $generator->reflectionKey(new ReflectionClass(UserDto::class)));
        $this->assertSame('property.' . UserDto::class . '::$name', $generator->reflectionKey(new ReflectionProperty(UserDto::class, 'name')));
        $this->assertSame(
            'parameter.' . UserDto::class . '::__construct:$name',
            $generator->reflectionKey(new ReflectionParameter([UserDto::class, '__construct'], 'name')),
        );
        $this->assertSame('function.' . UserDto::class . '::__construct', $generator->reflectionKey(new ReflectionMethod(UserDto::class, '__construct')));
        $this->assertSame('function.' . $function->getName(), $generator->reflectionKey($function));
    }

    public function testDeclaringClassKeyIsNullOnlyForFunctionParameter(): void
    {
        $generator = new ReflectionMetadataKeyGenerator();
        $function = new ReflectionFunction('strlen');

        $this->assertSame(UserDto::class, $generator->declaringClassKey(new ReflectionClass(UserDto::class)));
        $this->assertSame(UserDto::class, $generator->declaringClassKey(new ReflectionProperty(UserDto::class, 'name')));
        $this->assertSame(UserDto::class, $generator->declaringClassKey(new ReflectionParameter([UserDto::class, '__construct'], 'name')));
        $this->assertNull($generator->declaringClassKey($function->getParameters()[0]));
    }
}
