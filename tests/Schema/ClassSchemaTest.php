<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Schema;

use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use Ufo\DTO\Factory\DefaultDTOTransformerFromArrayFactory;
use Ufo\DTO\Factory\DefaultSchemaFactory;
use Ufo\DTO\Schema\Cache\CachedClassSchemaProvider;
use Ufo\DTO\Schema\ClassSchemaFactory;
use Ufo\DTO\Schema\ClassSchemaProvider;
use Ufo\DTO\Schema\Meta\SerializationContextAttributeReader;
use Ufo\DTO\Schema\Type\NamespaceKeyAnalyzer;
use Ufo\DTO\Tests\Fixtures\DTO\CallerNamespaceDTO;
use Ufo\DTO\Tests\Fixtures\DTO\DefaultValuesDTO;
use Ufo\DTO\Tests\Fixtures\DTO\DtoDefaultsDTO;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO2;
use Ufo\DTO\Tests\Fixtures\DTO\UserDto;
use Ufo\DTO\Tests\Fixtures\Enum\IntEnum;
use Ufo\DTO\Tests\Fixtures\DTO\SerializationContextDTO;
use Ufo\DTO\Tests\Support\TransformerFactory;
use Ufo\DTO\Transformer\Context\NormalizationContext;
use Ufo\DTO\Transformer\Converter\DateTimeConverter;
use Ufo\DTO\Transformer\DTOFromArrayTransformer;

final class ClassSchemaTest extends TestCase
{
    public function testSchemaDependingOnCallerNamespacesIsResolvedPerContext(): void
    {
        $transformer = TransformerFactory::fromArrayTransformer();
        $data = ['item' => ['id' => 1, 'name' => 'Item']];

        $first = $transformer->transformFromArray(CallerNamespaceDTO::class, $data, namespaces: ['Item' => DummyDTO::class]);
        $second = $transformer->transformFromArray(CallerNamespaceDTO::class, $data, namespaces: ['Item' => DummyDTO2::class]);
        $third = $transformer->transformFromArray(
            CallerNamespaceDTO::class,
            $data,
            namespaces: ['Item' => DummyDTO::class, 'Unrelated' => DummyDTO2::class],
        );

        $this->assertInstanceOf(DummyDTO::class, $first->item);
        $this->assertInstanceOf(DummyDTO2::class, $second->item);
        $this->assertInstanceOf(DummyDTO::class, $third->item);
    }

    public function testClassSchemaCacheIsBoundedAndCaseInsensitive(): void
    {
        [$typeSchemaResolver, $metadataProvider, , $validator, $converter] = TransformerFactory::fromArrayFactoryArguments();
        $schemaFactory = new ClassSchemaFactory(
            $metadataProvider,
            $typeSchemaResolver,
            (new DefaultSchemaFactory())->createDefaultValueResolver($metadataProvider),
            new NamespaceKeyAnalyzer($metadataProvider),
            new SerializationContextAttributeReader(),
        );
        $schemaProvider = new class (new ClassSchemaProvider($schemaFactory, $schemaFactory)) extends CachedClassSchemaProvider {
            protected const int SCHEMA_LIMIT = 2;

            public function schemaCount(): int
            {
                return count($this->schemas);
            }

            public function nameCount(): int
            {
                return count($this->schemasByName);
            }
        };

        $transformer = (new DefaultDTOTransformerFromArrayFactory($typeSchemaResolver, $metadataProvider, $schemaProvider, $validator, $converter))->create();
        $schemaProvider = (new \ReflectionProperty(DTOFromArrayTransformer::class, 'schemaProvider'))->getValue($transformer);

        $transformer->transformFromArray(DummyDTO::class, ['id' => 1, 'name' => 'a']);
        $transformer->transformFromArray(strtoupper(DummyDTO::class), ['id' => 1, 'name' => 'a']);
        $this->assertSame(1, $schemaProvider->schemaCount());

        $transformer->transformFromArray(DummyDTO2::class, ['id' => 1, 'name' => 'a']);
        $user = $transformer->transformFromArray(UserDto::class, ['name' => 'u', 'email' => 'e', 'currentTime' => 1_746_948_360]);
        $dummy = $transformer->transformFromArray(DummyDTO::class, ['id' => 2, 'name' => 'b']);

        $this->assertSame(2, $schemaProvider->schemaCount());
        $this->assertSame(2, $schemaProvider->nameCount());
        $this->assertInstanceOf(UserDto::class, $user);
        $this->assertSame(2, $dummy->id);
    }

    public function testPropertySchemaKeepsSerializationContextValues(): void
    {
        $schema = TransformerFactory::schemaProvider()->schema(SerializationContextDTO::class);
        $contexts = [];
        foreach ($schema->properties as $property) {
            $contexts[$property->name] = $property->serializationContext;
        }

        $this->assertSame([
            'createdAt' => [NormalizationContext::DATE_FORMAT => DateTimeInterface::ATOM],
            'status' => [],
            'publishedAt' => [DateTimeConverter::CONTEXT_OUTPUT_FORMAT => 'Y-m-d'],
        ], $contexts);
    }

    public function testMissingKeysUseDefaultsResolvedOncePerClass(): void
    {
        $transformer = TransformerFactory::fromArrayTransformer();

        foreach ([1, 2] as $attempt) {
            $dto = $transformer->transformFromArray(DefaultValuesDTO::class, []);

            $this->assertSame(5, $dto->typed, "attempt $attempt");
            $this->assertNull($dto->untyped);
            $this->assertSame(['a', 'b'], $dto->list);
            $this->assertSame(3, $dto->assigned);
            $this->assertSame(7, $dto->promoted);
            $this->assertNull($dto->untypedPromoted);
            $this->assertNull($dto->nullableEnum);
            $this->assertSame(IntEnum::B, $dto->level);
            $this->assertSame(['x'], $dto->bag->getArrayCopy());
            $this->assertSame(['y'], $dto->intersection->getArrayCopy());
        }

        $first = $transformer->transformFromArray(DefaultValuesDTO::class, []);
        $second = $transformer->transformFromArray(DefaultValuesDTO::class, []);
        $this->assertNotSame($first->bag, $second->bag);

        $renamed = $transformer->transformFromArray(DefaultValuesDTO::class, ['p' => 11], renameKey: ['promoted' => 'p']);
        $this->assertSame(11, $renamed->promoted);
        $this->assertSame(IntEnum::B, $renamed->level);
        $this->assertSame(['x'], $renamed->bag->getArrayCopy());

        $dto = $transformer->transformFromArray(DefaultValuesDTO::class, ['promoted' => 9, 'list' => ['c']]);
        $this->assertSame(9, $dto->promoted);
        $this->assertSame(['c'], $dto->list);
        $this->assertSame(5, $dto->typed);
    }

    public function testMissingKeysUseDtoObjectDefaults(): void
    {
        $transformer = TransformerFactory::fromArrayTransformer();

        foreach ([[[], []], [['t' => 'renamed'], ['title' => 't']]] as [$data, $renameKey]) {
            $first = $transformer->transformFromArray(DtoDefaultsDTO::class, $data, $renameKey);
            $second = $transformer->transformFromArray(DtoDefaultsDTO::class, $data, $renameKey);

            $this->assertSame('plain-default', $first->plain->name);
            $this->assertSame('nullable-default', $first->nullable?->name);
            $this->assertSame('attr-default', $first->withAttr->name);
            $this->assertNotSame($first->plain, $second->plain);
            $this->assertNotSame($first->nullable, $second->nullable);
            $this->assertNotSame($first->withAttr, $second->withAttr);

            $first->plain->name = 'mutated';
            $this->assertSame('plain-default', $transformer->transformFromArray(DtoDefaultsDTO::class, $data, $renameKey)->plain->name);
        }

        $passed = $transformer->transformFromArray(DtoDefaultsDTO::class, [
            'plain' => ['id' => 7, 'name' => 'from-payload'],
            'nullable' => null,
            'withAttr' => ['id' => 8, 'name' => 'attr-payload'],
        ]);

        $this->assertSame('from-payload', $passed->plain->name);
        $this->assertNull($passed->nullable);
        $this->assertSame('attr-payload', $passed->withAttr->name);
    }

    public function testMissingRequiredKeyStillThrows(): void
    {
        $transformer = TransformerFactory::fromArrayTransformer();

        foreach ([1, 2] as $attempt) {
            try {
                $transformer->transformFromArray(DummyDTO::class, ['name' => 'no-id']);
                $this->fail("attempt $attempt: expected exception");
            } catch (\InvalidArgumentException $e) {
                $this->assertSame("Missing required key for property: 'id'", $e->getMessage());
            }
        }
    }

    public function testRepeatedHydrationReturnsEqualObjects(): void
    {
        $transformer = TransformerFactory::fromArrayTransformer();
        $data = ['name' => 'User', 'email' => 'user@example.com', 'currentTime' => 1_746_948_360];

        $first = $transformer->transformFromArray(UserDto::class, $data);
        $second = $transformer->transformFromArray(UserDto::class, $data);

        $this->assertEquals($first, $second);
        $this->assertNotSame($first, $second);
    }
}
