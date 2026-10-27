# DTO Transformer

`ufo-tech/dto-transformer` is a PHP 8.4+ library for converting DTO objects to arrays and hydrating DTO objects from arrays.

The current implementation is built around replaceable services:

- `DTOTransformer` is the main facade/service.
- `DTOFromArrayTransformer` hydrates arrays into DTO objects.
- `DTOToArrayTransformer` normalizes DTO objects into arrays.
- `HydratorInterface` implementations resolve typed values during hydration.
- `NormalizerInterface` implementations normalize values during serialization.
- Reflection metadata, DocBlocks, strict mode and type schemas are centralized and cacheable.

## Installation

```bash
composer require ufo-tech/dto-transformer
```

Requirements:

- PHP `>=8.4`
- `ext-intl`
- `symfony/serializer`
- `symfony/validator`
- `symfony/cache-contracts`
- `phpdocumentor/reflection-docblock`
- `phpdocumentor/type-resolver`

## Quick Start

```php
use Ufo\DTO\DTOTransformer;
use Ufo\DTO\Factory\DefaultDTOTransformerFactory;

final class UserDto
{
    public function __construct(
        public string $name,
        public string $email,
    ) {}
}

DTOTransformer::boot(DefaultDTOTransformerFactory::default()->create());

$user = DTOTransformer::fromArray(UserDto::class, [
    'name' => 'Alex',
    'email' => 'alex@example.com',
]);

$array = DTOTransformer::toArray($user);
```

Boot the transformer once per process. `DTOTransformer::fromArray()`, `DTOTransformer::toArray()` and `DTOTransformer::transformFromArray()` are static and use the booted instance; before `DTOTransformer::boot()` they throw `NotInitializeException`. `transformToArray()` is an instance method and works on any created transformer.

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
- one class schema provider (`DefaultSchemaFactory::createClassSchemaProvider()`) shared by both directions
- `DefaultDTOTransformerFromArrayFactory`
- `DefaultDTOTransformerToArrayFactory`
- `DefaultNormalizerChainFactory`

Transformers created by one factory share one transformer graph and its caches, so `DTOTransformer` and `ServiceTransformer` should come from the same factory:

```php
$factory = DefaultDTOTransformerFactory::default(persistentCache: $cache);

DTOTransformer::boot($factory->create());
ServiceTransformer::boot($factory->create(ServiceTransformer::class));
```

For custom wiring, instantiate `DefaultDTOTransformerFactory` with your own `DTOTransformerFromArrayFactoryInterface` and `DTOTransformerToArrayFactoryInterface` implementations.

Optional arguments of `default()`:

- `persistentCache` — a Symfony cache for reflection metadata and class schemas;
- `runtimeCacheLimit` — the in-process metadata cache size (default `2048` entries; `0` disables the limit and lets the cache grow without bound);
- `validator` — a Symfony `ValidatorInterface` for `AttrAssertions` (see below).

`default()` builds a new composition with empty in-process caches on every call. Create the transformer once and reuse it: boot it once or register it as a shared service.

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

The default normalizer chain is created by `DefaultNormalizerChainFactory`:

```php
new NormalizerChain([
    new ScalarNormalizer(),
    new EnumNormalizer(),
    new DateTimeNormalizer($dateTimeValueConverter),
    new ArrayNormalizer(),
    new DTONormalizer($schemaProvider, new PropertyContextCache()),
]);
```

Normalizers implement:

```php
use Ufo\DTO\Transformer\Context\NormalizationContext;
use Ufo\DTO\Transformer\Normalizer\NormalizerInterface;

interface NormalizerInterface
{
    public function supports(mixed $data, NormalizationContext $context): bool;

    public function normalize(mixed $data, NormalizationContext $context): mixed;
}
```

`DTONormalizer` reads DTO properties through metadata, applies `renameKey`, `publicOnly`, `SerializationContext`, smart-array output and recursively delegates nested values back to the chain.

### Serialization Context

```php
use DateTimeInterface;
use Ufo\DTO\Attributes\SerializationContext;
use Ufo\DTO\Transformer\Context\NormalizationContext;

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
$dto = DTOTransformer::fromArray(
    UserDto::class,
    data: ['full_name' => 'Alex', 'email' => 'alex@example.com'],
    renameKey: ['name' => 'full_name'],
    namespaces: [],
    context: [],
);
```

`renameKey` maps a property to the payload key it comes from. Renamed properties are hydrated exactly like the others: a missing renamed key falls back to the default value, `#[Ignore]` still applies, and renaming to `null` means the key is missing. Error messages about a missing required key name the payload key.

When the payload has no key for a property, the default value is used: a property default, a constructor parameter default (including objects such as `new Address()`, created per hydrated object), otherwise the hydration fails with `Missing required key for property`.

The default hydration chain is created by `DefaultDTOTransformerFromArrayFactory`:

```php
new UnionHydrator();
new EnumHydrator();
new ScalarHydrator();
new ReflectionClassHydrator();
new ReflectionParameterHydrator();
new ReflectionPropertyHydrator();
new DateTimeHydrator($dateTimeValueConverter);
new DTOHydrator($transformer, $customTransformers);
new ArrayItemsHydrator();
new AdditionalHydrator();
new MixedHydrator();
```

Hydrators implement:

```php
use Ufo\DTO\Transformer\Context\TransformationContext;
use Ufo\DTO\Transformer\Hydrator\HydratorInterface;

interface HydratorInterface
{
    public function supports(array $schema): bool;

    public function resolve(
        array $schema,
        mixed $value,
        TransformationContext $context,
    ): mixed;
}
```

Schemas are compiled once into `SchemaNode` trees: each node keeps the selected hydrator and the compiled `items`, `additionalProperties` and `oneOf` children. A hydrator of nested schemas can implement `SchemaNodeHydratorInterface::resolveNode()` to use these children instead of selecting a hydrator for every value; `ArrayItemsHydrator`, `AdditionalHydrator` and `UnionHydrator` do so.

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

### Namespaces

`namespaces` maps short class names from DocBlocks, smart arrays and `fromArray('A|B')` to classes or namespaces: `['Item' => App\Dto\Item::class]`, `[DTOTransformer::DTO_NS_KEY => 'App\Dto']`. Namespaces of the class being hydrated take precedence over the ones passed by the caller.

Pass namespaces from a finite set defined in code or configuration, never from the payload or per-request data. For classes whose types depend on namespaces (short class names in DocBlocks, non-class native object types), hydration schemas are cached per combination of namespace values: in-process caches are bounded, but every new combination is resolved again and adds entries to the persistent cache.

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

A class passed in `AttrDTO::C_TRANSFORMER` must implement `StaticDTOFromArrayTransformerInterface`.

### `AttrAssertions`

`AttrAssertions` validates the raw payload value with Symfony Validator constraints before hydration; a violation throws `BadParamException`. Constraints apply only when the key is present in the payload: a missing key falls back to the default value (or fails as a missing required key), an explicit `null` is validated as `null`:

```php
use Symfony\Component\Validator\Constraints as Assert;
use Ufo\DTO\Attributes\AttrAssertions;

final class UserDto
{
    public function __construct(
        #[AttrAssertions([new Assert\NotBlank(), new Assert\Length(min: 3)])]
        public string $name,
    ) {}
}
```

Pass your application validator (with custom constraint validators and translations) through the factory:

```php
$transformer = DefaultDTOTransformerFactory::default(validator: $validator)->create();
```

Without it, `default()` provides one lazy default validator (`Validation::createValidator()`) shared by all transformers of the factory; it is built on the first `AttrAssertions` check.

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

Add custom object-to-array behavior with `NormalizerInterface`:

```php
use Ufo\DTO\Transformer\Context\NormalizationContext;
use Ufo\DTO\Transformer\Normalizer\NormalizerInterface;

final class MoneyNormalizer implements NormalizerInterface
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

Add custom array-to-object behavior with `HydratorInterface`:

```php
use Ufo\DTO\Transformer\Context\TransformationContext;
use Ufo\DTO\Transformer\Hydrator\HydratorInterface;

final class UuidHydrator implements HydratorInterface
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

Optional interfaces for a custom `DTOFromArrayTransformerInterface` implementation:

- `DTOFromContextTransformerInterface` — receives the `TransformationContext` object for nested DTOs instead of a rebuilt array;
- `DTOCandidateFilterInterface` — cheaply rejects union candidates (`fromArray('A|B')`, `A|B` properties) whose hydration is guaranteed to fail, so they are not hydrated speculatively. Return `true` only when failure is certain.

## Symfony Integration

Install the package:

```bash
composer require ufo-tech/dto-transformer
```

Build the transformers with `DefaultDTOTransformerFactory`, the same composition as outside Symfony:

```yaml
services:
  ufo.dto.cache:
    class: Symfony\Component\Cache\Adapter\FilesystemAdapter
    arguments:
      $namespace: 'dto_transformer'
      $directory: '%kernel.cache_dir%/dto-transformer'

  Ufo\DTO\Factory\DefaultDTOTransformerFactory:
    factory: [ 'Ufo\DTO\Factory\DefaultDTOTransformerFactory', 'default' ]
    arguments:
      $persistentCache: '@ufo.dto.cache'
      $validator: '@validator'

  Ufo\DTO\DTOTransformer:
    public: true
    factory: [ '@Ufo\DTO\Factory\DefaultDTOTransformerFactory', 'create' ]

  Ufo\DTO\ServiceTransformer:
    public: true
    factory: [ '@Ufo\DTO\Factory\DefaultDTOTransformerFactory', 'create' ]
    arguments: [ 'Ufo\DTO\ServiceTransformer' ]
```

- `$persistentCache` is optional: drop the argument and the `ufo.dto.cache` service to keep the cache in memory only. Clear the persistent cache on deploy.
- `$validator: '@validator'` makes `AttrAssertions` use the application validator (requires `framework.validation`). Without it the factory creates its own default validator.
- One factory service means `DTOTransformer` and `ServiceTransformer` share one object graph and its caches. Drop `ServiceTransformer` if you do not use it.

`public: true` is required if the transformer is accessed through:

```php
$container->get(DTOTransformer::class)
```

or inside `Bundle::boot()`. The static methods (`fromArray()`, `toArray()`) work only after the container service is booted once, for example in `Bundle::boot()`:

```php
if (!DTOTransformer::isInitialized()) {
    DTOTransformer::boot($this->container->get(DTOTransformer::class));
}
if (!ServiceTransformer::isInitialized()) {
    ServiceTransformer::boot($this->container->get(ServiceTransformer::class));
}
```

Example with an injected transformer — call the instance methods:

```php
use Ufo\DTO\DTOTransformer;

final class UserService
{
    public function __construct(
        private readonly DTOTransformer $transformer,
    ) {
    }

    public function handle(array $payload): array
    {
        $user = $this->transformer->transformFromArray(UserDTO::class, $payload);

        return $this->transformer->transformToArray($user);
    }
}
```

The static `DTOTransformer::fromArray()` / `toArray()` always use the booted instance, not the injected one.

## Performance

Recommendations:

- reuse one `DTOTransformer` service instance: `DefaultDTOTransformerFactory::default()` builds new caches on every call;
- create `DTOTransformer` and `ServiceTransformer` from one factory: they share class schemas;
- pass a Symfony cache adapter to `DefaultDTOTransformerFactory::default()`;
- prefer native types where possible: properties with native types only do not depend on caller namespaces;
- use DocBlocks for collections and unions where native types are not enough;
- pass namespaces from a fixed set (see [Namespaces](#namespaces));
- avoid rebuilding custom hydrator or normalizer chains per request.

In-process caches are bounded, so long-running workers keep a stable memory footprint: class schemas hold up to 3000 classes, other per-class caches up to 1024, the metadata cache holds `runtimeCacheLimit` entries.

## Testing

```bash
docker exec php_dto php vendor/bin/phpunit
```

Benchmarks (time, CPU, disk I/O, allocated and retained memory, memory growth):

```bash
docker exec php_dto composer bench                                  # warm, table
docker exec php_dto composer bench -- --mode=reset                  # fresh transformer graph before each sample
docker exec php_dto composer bench -- --mode=cold-process           # first call in a new PHP process
docker exec php_dto composer bench -- --mode=cold-persistent        # same, with a filled persistent cache
docker exec php_dto composer bench -- --mode=cold-persistent-empty  # same, persistent cache empty (first run after deploy)
docker exec php_dto composer bench -- --mode=growth                 # memory of a long-running process
docker exec php_dto composer bench -- --filter=fromArray --note="what and why"
docker exec php_dto php benchmarks/compare.php benchmarks/results/<run-a> benchmarks/results/<run-b>
```

Every run is saved to `benchmarks/results/<date>_<mode>_php<version>_opcache-…_jit-…_xdebug-…_<commit>[-dirty]/`
with `report.md` (command, note, environment, tables) and `results.json` (raw samples). The folder is not tracked by
git; `--no-save` skips saving.

Methodology: the `ufo-tech/benchmark-author` skill and the `ufo-tech/php-benchmarks` rule of the UFO Tech catalog.
I/O columns need Linux (`/proc/self/io`); elsewhere the report lists them under "Not measured".

## License

MIT
