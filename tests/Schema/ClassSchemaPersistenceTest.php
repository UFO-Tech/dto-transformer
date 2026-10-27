<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Schema;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Ufo\DTO\Tests\Fixtures\DTO\CallerNamespaceDTO;
use Ufo\DTO\Tests\Fixtures\DTO\SerializationContextDTO;
use Ufo\DTO\Tests\Fixtures\Enum\StringEnum;
use Ufo\DTO\Transformer\Context\NormalizationContext;
use Ufo\DTO\Tests\Fixtures\DTO\DefaultValuesDTO;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO2;
use Ufo\DTO\Tests\Fixtures\DTO\MemberWithFriendsDTO;
use Ufo\DTO\Tests\Fixtures\Enum\IntEnum;
use Ufo\DTO\Tests\Support\TransformerFactory;
use Ufo\DTO\Transformer\DTOFromArrayTransformerInterface;

final class ClassSchemaPersistenceTest extends TestCase
{
    public function testSchemaIsRestoredFromPersistentCacheWithOneEntryForClassAndOneForTypeSchemas(): void
    {
        $cache = $this->serializingCache();
        $this->transformer($cache)->transformFromArray(DummyDTO::class, ['id' => 1, 'name' => 'first']);

        $cache->gets = 0;
        $dto = $this->transformer($cache)->transformFromArray(DummyDTO::class, ['id' => 2, 'name' => 'second']);

        $this->assertSame(2, $cache->gets);
        $this->assertSame(2, $dto->id);
        $this->assertSame('second', $dto->name);
    }

    public function testRestoredSchemaKeepsDefaultsAndNestedResults(): void
    {
        $cache = $this->serializingCache();
        $member = [
            'user' => ['name' => 'u', 'email' => 'e', 'currentTime' => 1_746_948_360],
            'friends' => [['name' => 'f', 'email' => 'fe', 'currentTime' => 1_746_948_361]],
        ];

        $firstMember = $this->transformer($cache)->transformFromArray(MemberWithFriendsDTO::class, $member);
        $this->transformer($cache)->transformFromArray(DefaultValuesDTO::class, []);

        $restored = $this->transformer($cache);
        $defaults = $restored->transformFromArray(DefaultValuesDTO::class, []);

        $this->assertEquals($firstMember, $restored->transformFromArray(MemberWithFriendsDTO::class, $member));
        $this->assertSame(5, $defaults->typed);
        $this->assertNull($defaults->untypedPromoted);
        $this->assertSame(IntEnum::B, $defaults->level);
        $this->assertSame(['x'], $defaults->bag->getArrayCopy());
    }

    public function testRestoredSchemasStayPerNamespaceContext(): void
    {
        $cache = $this->serializingCache();
        $data = ['item' => ['id' => 1, 'name' => 'Item']];
        $this->transformer($cache)->transformFromArray(CallerNamespaceDTO::class, $data, namespaces: ['Item' => DummyDTO::class]);

        $restored = $this->transformer($cache);
        $first = $restored->transformFromArray(CallerNamespaceDTO::class, $data, namespaces: ['Item' => DummyDTO::class]);
        $second = $restored->transformFromArray(CallerNamespaceDTO::class, $data, namespaces: ['Item' => DummyDTO2::class]);

        $this->assertInstanceOf(DummyDTO::class, $first->item);
        $this->assertInstanceOf(DummyDTO2::class, $second->item);
    }

    public function testStaleDescriptionInPersistentCacheIsRebuilt(): void
    {
        $cache = $this->serializingCache();
        $cache->values[md5('class_schema.' . DummyDTO::class)] = serialize([
            'namespaces' => [],
            'namespaceKeys' => [],
            'properties' => ['removedProperty' => ['promoted' => true]],
        ]);

        $dto = $this->transformer($cache)->transformFromArray(DummyDTO::class, ['id' => 3, 'name' => 'fresh']);

        $this->assertSame(3, $dto->id);
        $this->assertSame('fresh', $dto->name);
        $this->assertSame(['id', 'name'], array_keys(unserialize($cache->values[md5('class_schema.' . DummyDTO::class)])['properties']));
    }

    public function testSerializationContextIsRestoredFromPersistentCache(): void
    {
        $cache = $this->serializingCache();
        $dto = new SerializationContextDTO(new DateTimeImmutable('2026-05-08 14:30:00+03:00'), StringEnum::B, new DateTimeImmutable('2026-05-09 10:00:00+03:00'));
        $expected = ['createdAt' => '2026-05-08T14:30:00+03:00', 'status' => 'b', 'publishedAt' => '2026-05-09'];
        $this->assertSame($expected, TransformerFactory::normalizerChain($cache)->normalize($dto, NormalizationContext::create()));

        $this->assertSame($expected, TransformerFactory::normalizerChain($cache)->normalize($dto, NormalizationContext::create()));
    }

    public function testDescriptionWithoutSerializationContextKeyIsRebuilt(): void
    {
        $cache = $this->serializingCache();
        $dto = new SerializationContextDTO(new DateTimeImmutable('2026-05-08 14:30:00+03:00'), StringEnum::B, new DateTimeImmutable('2026-05-09 10:00:00+03:00'));
        TransformerFactory::normalizerChain($cache)->normalize($dto, NormalizationContext::create());

        $key = md5('class_schema.' . SerializationContextDTO::class);
        $description = unserialize($cache->values[$key]);
        foreach ($description['properties'] as &$property) {
            unset($property['serializationContext']);
        }
        unset($property);
        $cache->values[$key] = serialize($description);

        $result = TransformerFactory::normalizerChain($cache)->normalize($dto, NormalizationContext::create());

        $this->assertSame('2026-05-08T14:30:00+03:00', $result['createdAt']);
        $this->assertSame('2026-05-09', $result['publishedAt']);
    }

    private function transformer(CacheInterface $cache): DTOFromArrayTransformerInterface
    {
        return TransformerFactory::fromArrayFactory($cache)->create();
    }

    private function serializingCache(): CacheInterface
    {
        return new class implements CacheInterface {
            public int $gets = 0;

            /**
             * @var array<string, string>
             */
            public array $values = [];

            public function get(string $key, callable $callback, ?float $beta = null, ?array &$metadata = null): mixed
            {
                $this->gets++;
                $this->values[$key] ??= serialize($callback());

                return unserialize($this->values[$key]);
            }

            public function delete(string $key): bool
            {
                unset($this->values[$key]);

                return true;
            }
        };
    }
}
