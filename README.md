# DTO Transformer

`ufo-tech/dto-transformer` is a PHP 8.3+ library for converting DTO objects to arrays and hydrating DTO objects from arrays.

The current implementation is built around replaceable services:

- `DTOTransformer` is the main facade/service.
- `DTOFromArrayTransformer` hydrates arrays into DTO objects.
- `DTOToArrayTransformer` normalizes DTO objects into arrays.
- `ParamHydratorInterface` implementations resolve typed values during hydration.
- `PropertyNormalizerInterface` implementations normalize values during serialization.
- Reflection metadata, DocBlocks, strict mode and type schemas are centralized and cacheable.

## Installation

```bash
composer require ufo-tech/dto-transformer
```

Requirements:

- PHP `>=8.3`
- `ext-intl`
- `symfony/serializer`
- `symfony/validator`
- `symfony/cache-contracts`
- `phpdocumentor/reflection-docblock`
- `phpdocumentor/type-resolver`

## Quick Start

```php
use Ufo\DTO\Factory\DefaultDTOTransformerFactory;

final class UserDto
{
    public function __construct(
        public string $name,
        public string $email,
    ) {}
}

$transformer = DefaultDTOTransformerFactory::default()->create();

$user = $transformer->transformFromArray(UserDto::class, [
    'name' => 'Alex',
    'email' => 'alex@example.com',
]);

$array = $transformer->transformToArray($user);
```

Static calls are still supported, but the transformer must be booted first:

```php
use Ufo\DTO\DTOTransformer;
use Ufo\DTO\Factory\DefaultDTOTransformerFactory;

DTOTransformer::boot(DefaultDTOTransformerFactory::default()->create());

$dto = DTOTransformer::fromArray(UserDto::class, $payload);
$array = DTOTransformer::toArray($dto);
```

If the static facade is used before `DTOTransformer::boot()`, `NotInitializeException` is thrown.

## Default Factories

Use `DefaultDTOTransformerFactory::default()` for the standard composition:

```php
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Ufo\DTO\Factory\DefaultDTOTransformerFactory;

$cache = new FilesystemAdapter(namespace: 'dto_transformer');

$transformer = DefaultDTOTransformerFactory::default(
    persistentCache: $cache,
)->create();
```

The default factory wires:

- `RuntimeReflectionCache`
- `ReflectionMetadataProvider`
- `StrictModeResolver`
- `ReflectionTypeSchemaResolver`
- `DefaultDTOTransformerFromArrayFactory`
- `DefaultDTOTransformerToArrayFactory`
- `DefaultPropertyNormalizerFactory`

For custom wiring, instantiate `DefaultDTOTransformerFactory` with your own `DTOTransformerFromArrayFactoryInterface` and `DTOTransformerToArrayFactoryInterface` implementations.

## Object To Array

```php
$array = $transformer->transformToArray(
    dto: $dto,
    renameKey: ['name' => 'full_name'],
    asSmartArray: false,
    publicOnly: true,
    context: [],
);
```

The default normalizer chain is created by `DefaultPropertyNormalizerFactory`:

```php
new PropertyNormalizer([
    new ScalarValueConverter(),
    new EnumNormalizer(),
    new DateTimeNormalizer($dateTimeValueConverter),
    new ArrayNormalizer(),
    new DtoNormalizer($metadataProvider, $serializationContextProvider),
]);
```

Normalizers implement:

```php
use Ufo\DTO\Interfaces\Normalizer\PropertyNormalizerInterface;
use Ufo\DTO\VO\NormalizationContext;

interface PropertyNormalizerInterface
{
    public function supports(mixed $data, NormalizationContext $context): bool;

    public function normalize(mixed $data, NormalizationContext $context): mixed;
}
```

`DtoNormalizer` reads DTO properties through metadata, applies `renameKey`, `publicOnly`, `SerializationContext`, smart-array output and recursively delegates nested values back to the chain.

### Serialization Context

```php
use DateTimeInterface;
use Ufo\DTO\Attributes\SerializationContext;
use Ufo\DTO\VO\NormalizationContext;

final class EventDto
{
    public function __construct(
        #[SerializationContext([
            NormalizationContext::DATE_FORMAT => DateTimeInterface::ATOM,
            NormalizationContext::DATE_TIMEZONE => 'UTC',
        ])]
        public DateTimeImmutable $createdAt,
    ) {}
}
```

Default enum output:

- backed enums are normalized to their backing value;
- unit enums are normalized to the case name.

Default DateTime output format is `Y-m-d H:i:s`. Use `SerializationContext` or call-level `context` to change date format, timezone or timestamp output.

## Array To Object

```php
$dto = $transformer->transformFromArray(UserDto::class, [
    'name' => 'Alex',
    'email' => 'alex@example.com',
]);
```

The default hydration chain is created by `DefaultDTOTransformerFromArrayFactory` and `DefaultParamHydratorFactory`:

```php
new UnionParamHydrator();
new EnumParamHydrator();
new ScalarParamHydrator();
new ReflectionClassHydrator();
new ReflectionParameterHydrator();
new ReflectionPropertyHydrator();
new DateTimeHydrator();
new DtoHydrator($transformer, $customTransformers);
new ArrayItemsHydrator();
new AdditionalHydrator();
new MixedHydrator();
```

Hydrators implement:

```php
use Ufo\DTO\Interfaces\Hydrator\ParamHydratorInterface;
use Ufo\DTO\VO\TransformationContext;

interface ParamHydratorInterface
{
    public function supports(array $schema): bool;

    public function resolve(
        array $schema,
        mixed $value,
        TransformationContext $context,
    ): mixed;
}
```

`ReflectionTypeSchemaResolver` builds schemas from native PHP types, DocBlocks, `AttrDTO`, namespaces and strict-mode metadata. If no schema is available, the original value is kept.

### Native Types

```php
final class ProfileDto
{
    public function __construct(
        public string $name,
        public int $age,
        public bool $active,
    ) {}
}
```

### Nested DTOs

```php
final class OrderDto
{
    public function __construct(
        public UserDto $user,
    ) {}
}

$order = DTOTransformer::fromArray(OrderDto::class, [
    'user' => [
        'name' => 'Alex',
        'email' => 'alex@example.com',
    ],
]);
```

### Collections

Collections can be described with DocBlocks:

```php
final class TeamDto
{
    /**
     * @param UserDto[] $users
     */
    public function __construct(
        public array $users,
    ) {}
}
```

Or with `AttrDTO`:

```php
use Ufo\DTO\Attributes\AttrDTO;

final class TeamDto
{
    public function __construct(
        #[AttrDTO(UserDto::class, context: [
            AttrDTO::C_COLLECTION => true,
        ])]
        public array $users,
    ) {}
}
```

### Smart Arrays

Smart arrays include the DTO class name in the payload:

```php
$array = DTOTransformer::toArray($dto, asSmartArray: true);

// [
//     'name' => 'Alex',
//     '$className' => App\Dto\UserDto::class,
// ]
```

They can be restored with namespace aliases:

```php
$dto = DTOTransformer::fromSmartArray($array, namespaces: [
    DTOTransformer::DTO_NS_KEY => App\Dto::class,
]);
```

### DateTime

Hydration supports `DateTimeImmutable`, `DateTime`, `DateTimeInterface`, strings, integer timestamps and float timestamps with microseconds.

```php
final class EventDto
{
    public function __construct(
        public DateTimeImmutable $createdAt,
    ) {}
}

$dto = DTOTransformer::fromArray(EventDto::class, [
    'createdAt' => '2026-05-08 14:30:00',
]);
```

### Enums

Backed enums use `tryFrom()`. Integer-backed enums also accept numeric strings. Unit enums are resolved by case name, case-insensitively.

```php
enum Status: string
{
    case Active = 'active';
}

final class UserDto
{
    public function __construct(
        public Status $status,
    ) {}
}
```

## Attributes

### `AttrDTO`

`AttrDTO` gives explicit denormalization hints:

```php
use Ufo\DTO\Attributes\AttrDTO;

final class WrapperDto
{
    public function __construct(
        #[AttrDTO(UserDto::class, context: [
            AttrDTO::C_COLLECTION => true,
            AttrDTO::C_STRICT => true,
        ])]
        public array $users,
    ) {}
}
```

Supported context keys:

- `AttrDTO::C_COLLECTION`
- `AttrDTO::C_STRICT`
- `AttrDTO::C_NS`
- `AttrDTO::C_RENAME_KEYS`
- `AttrDTO::C_TRANSFORMER`
- `AttrDTO::C_IS_ENUM`
- `AttrDTO::C_PROPERTY`

### `StrictMode`

Strict mode turns hydration failures into `BadParamException`. Without strict mode, invalid nested values may be kept as-is.

```php
use Ufo\DTO\Attributes\StrictMode;

final class UserDto
{
    public function __construct(
        #[StrictMode]
        public int $age,
    ) {}
}
```

DocBlock strict mode is also supported:

```php
/**
 * @strictMode true
 */
final class UserDto
{
}
```

## Extensibility

Add custom object-to-array behavior with `PropertyNormalizerInterface`:

```php
use Ufo\DTO\Interfaces\Normalizer\PropertyNormalizerInterface;
use Ufo\DTO\VO\NormalizationContext;

final class MoneyNormalizer implements PropertyNormalizerInterface
{
    public function supports(mixed $data, NormalizationContext $context): bool
    {
        return $data instanceof Money;
    }

    public function normalize(mixed $data, NormalizationContext $context): string
    {
        return $data->currency . ' ' . number_format($data->amount / 100, 2);
    }
}
```

Add custom array-to-object behavior with `ParamHydratorInterface`:

```php
use Ufo\DTO\Interfaces\Hydrator\ParamHydratorInterface;
use Ufo\DTO\VO\TransformationContext;

final class UuidHydrator implements ParamHydratorInterface
{
    public function supports(array $schema): bool
    {
        return ($schema['format'] ?? null) === 'uuid';
    }

    public function resolve(array $schema, mixed $value, TransformationContext $context): Uuid
    {
        return Uuid::fromString((string) $value);
    }
}
```

For full control, provide your own factory implementations or construct `DTOFromArrayTransformer` / `DTOToArrayTransformer` with custom chains.

## Symfony Integration

Install dependencies:

```bash
composer require ufo-tech/dto-transformer
composer require phpdocumentor/reflection-docblock
```

Configure services:

```yaml
services:
  _defaults:
    autowire: true
    autoconfigure: true

  _instanceof:
    Ufo\DTO\Interfaces\Hydrator\ParamHydratorInterface:
      tags:
        - dto.param_hydrator

    Ufo\DTO\Interfaces\Normalizer\PropertyNormalizerInterface:
      tags:
        - dto.param_normalizer

  Ufo\DTO\:
    resource: '../vendor/ufo-tech/dto-transformer/src'
    exclude:
      - '../vendor/ufo-tech/dto-transformer/src/Annotations/'
      - '../vendor/ufo-tech/dto-transformer/src/Attributes/'

  phpDocumentor\Reflection\DocBlockFactoryInterface:
    class: phpDocumentor\Reflection\DocBlockFactory
    factory: [ 'phpDocumentor\Reflection\DocBlockFactory', 'createInstance' ]

  Ufo\DTO\Interfaces\Normalizer\PropertyNormalizerChainInterface:
    class: Ufo\DTO\Transformer\Normalizer\PropertyNormalizer
    arguments:
      $converters: !tagged_iterator dto.param_normalizer

  Ufo\DTO\Interfaces\DTOFromArrayTransformerInterface:
    factory: [ '@Ufo\DTO\Factory\DefaultDTOTransformerFromArrayFactory', 'create' ]

  Ufo\DTO\Interfaces\DTOToArrayTransformerInterface:
    factory: [ '@Ufo\DTO\Factory\DefaultDTOTransformerToArrayFactory', 'create' ]

  Ufo\DTO\DTOTransformer:
    public: true
    arguments:
      $fromArrayTransformer: '@Ufo\DTO\Interfaces\DTOFromArrayTransformerInterface'
      $toArrayTransformer: '@Ufo\DTO\Interfaces\DTOToArrayTransformerInterface'
```

`public: true` is required if the transformer is accessed through:

```php
$container->get(DTOTransformer::class)
```

or inside `Bundle::boot()`.

Cache adapters are optional.

```yaml
ufo.dto.cache:
  public: true
  class: Symfony\Component\Cache\Adapter\FilesystemAdapter
  arguments:
    $namespace: 'meta'
    $directory: '%rpc_cache_dirrectory%/dto-transformer'
    $defaultLifetime: '%rpc_filecache_ttl%'
```

Example:

```php
use Ufo\DTO\DTOTransformer;

final class UserService
{
    public function __construct(
        private readonly DTOTransformer $transformer,
    ) {
    }
}
```

```php
$user = $this->transformer->fromArray(UserDTO::class, [
    'name' => 'Alex',
]);

$array = $this->transformer->toArray($user);
```

## Performance

Recommendations:

- reuse one `DTOTransformer` service instance;
- pass a Symfony cache adapter to `DefaultDTOTransformerFactory::default()`;
- prefer native types where possible;
- use DocBlocks for collections and unions where native types are not enough;
- avoid rebuilding custom hydrator or normalizer chains per request.

## Testing

```bash
docker exec php_dto php vendor/bin/phpunit
```

Benchmarks:

```bash
composer bench
```

## License

MIT
