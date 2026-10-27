[Українська](README-UA.md)

# 3.0 — service architecture, caching, DateTime, strict mode

Tags: 3.0.0, 3.0.1, 3.0.4, 3.0.5 (3.0.2 existed only as an untagged commit, its change ships in 3.0.4; there is no 3.0.3). First released: 2026-05-25.

## Highlights

- **`DTOTransformer` is a facade over replaceable services**: chains of hydrators and normalizers, a type schema resolver and reflection metadata with a runtime LRU cache plus an optional persistent cache (`symfony/cache-contracts`).
- **The static API needs `boot()`**; from 3.0.4 `TransformerBootstrapper::boot()` boots `DTOTransformer` and `ServiceTransformer`.
- **DateTime** hydration and export with format and timezone context.
- **`#[StrictMode]` / `@strictMode`**, `#[SerializationContext]`, `AttrDTO::C_STRICT`, typed exceptions and a `$context` parameter throughout.

## What's new

### Services and factories (3.0.0)

- `DTOTransformer::__construct(Interfaces\DTOFromArrayTransformerInterface $fromArrayTransformer, Interfaces\DTOToArrayTransformerInterface $toArrayTransformer)`; instance methods `transformFromArray()` and `transformToArray()`.
- Static lifecycle: `DTOTransformer::boot(self $transformer)` (a second call throws `RuntimeException`), `reset()`, `isInitialized()`. Instances are kept per class, so `ServiceTransformer` is booted separately. Until then the static methods throw `Exceptions\NotInitializeException`.
- `Transformer\DTOFromArrayTransformer` and `Transformer\DTOToArrayTransformer` do the work.
- `Factory\DefaultDTOTransformerFactory::default(?CacheInterface $persistentCache = null, bool $recreate = false)` and `create(string $class = DTOTransformer::class)`; also `DefaultDTOTransformerFromArrayFactory` (custom `DTOFromArrayTransformerInterface` instances are used for nested DTOs), `DefaultDTOTransformerToArrayFactory`, `DefaultPropertyNormalizerFactory`, `DefaultMetadataFactory`.
- Hydrators in `Transformer\Hydrator` behind `Interfaces\Hydrator\ParamHydratorInterface` (`supports()`, `resolve()`), chained by `ParamHydrator`. Normalizers in `Transformer\Normalizer` behind `Interfaces\Normalizer\PropertyNormalizerInterface`, chained by `PropertyNormalizer`.
- Metadata in `Transformer\Metadata`: `RuntimeReflectionCache` (LRU, 2048 entries by default, optional persistent layer), `ReflectionMetadataProvider`, `PhpDocumentorDocBlockParser`, `StrictModeResolver`, `AttributeSerializationContextProvider`.

```php
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Ufo\DTO\Factory\DefaultDTOTransformerFactory;

$transformer = DefaultDTOTransformerFactory::default(
    persistentCache: new FilesystemAdapter(namespace: 'dto_transformer'),
)->create();

$dto = $transformer->transformFromArray(UserDto::class, $payload);
```

### `TransformerBootstrapper` (3.0.4, 3.0.5)

`Ufo\DTO\TransformerBootstrapper::boot()` boots `DTOTransformer` and `ServiceTransformer` from `DefaultDTOTransformerFactory::default()`, each only if it is not booted yet, so a second call is safe. `isInitialized()` (3.0.5) is true when both are booted.

```php
\Ufo\DTO\TransformerBootstrapper::boot();
```

### DateTime (3.0.0)

`DateTimeImmutable`, `DateTime` and `DateTimeInterface` are hydrated from a string, int or float timestamp. `toArray()` exports them as a string, `Y-m-d H:i:s` by default. Format and timezones come from `#[SerializationContext]` or the call `context`: `format`, `formats`, `output_format`, `timezone`, `input_timezone`, `output_timezone`, `input` / `output` = `timestamp`. `Transformer\Converter\DateTimeConverter` implements `Interfaces\Converter\DateTimeValueConverterInterface`.

### Strict mode (3.0.0)

- Values typed natively are resolved strictly: a failure throws `BadParamException("Failed to resolve X::$p: …")`.
- Values typed only by DocBlock are strict when `#[Attributes\StrictMode]` or the `@strictMode` tag is found on the property or parameter, the function, the class, its parents or interfaces. Otherwise a value that fails to convert is kept as is.
- `AttrDTO::C_STRICT => true` makes a collection fail on a bad item; without it the raw item is kept.

```php
#[AttrDTO(UserDto::class, context: [AttrDTO::C_COLLECTION => true, AttrDTO::C_STRICT => true])]
public array $users;
```

### Other additions (3.0.0)

- `#[Attributes\SerializationContext(array $context = [])]` on properties and parameters.
- Exceptions `InvalidCollectionValueException`, `InvalidEnumValueException`, `InvalidObjectValueException`, `InvalidScalarValueException`, `UnionTypeMismatchException` (all extend `BadParamException`) and `NotInitializeException` (extends `RuntimeException`, not wrapped by `fromArray()`).
- `Transformer\Converter\EnumConverter::toEnum(string $enumFQCN, string|int $value): UnitEnum`.
- Optional last parameter `array $context = []` on `fromArray()`, `toArray()`, `ArrayConstructibleTrait::fromArray()` and `ServiceTransformer`.
- `TypeHintResolver`: cases `CALLABLE`, `ITERABLE`; `reflectionTypeToSchema()`, `reflectionNamedTypeToSchema()`, `reflectionTypeAllowsArray()`, `isScalarSchema()`, `hasScalarType()`, `isCompositeSchema()`, `scalarTypes()`, `matchType()`.
- New dependency `symfony/cache-contracts ^3.5`.

## Changes and fixes

- **3.0.0**: an enum value that matches no case throws `InvalidEnumValueException` (2.x returned the raw value for a pure enum); an int-backed enum also accepts numeric strings. `EnumResolver::generateEnumSchema()` adds `classFQCN`.
- **3.0.0**: `jsonSchemaToPhp()` / `jsonSchemaToTypeDescription()` write a nullable as `null|Type` (was `?Type`).
- **3.0.0**: `AttrDTO::C_TRANSFORMER` is used only if the class implements `IDTOFromArrayTransformer`; before, the check ran on the default transformer.
- **3.0.0**: `toArray()` walks nested objects by reflection instead of calling their `IArrayConvertible::toArray()`; `$renameKey` reaches nested levels; a value no normalizer supports throws `BadParamException`. `#[Ignore]` is no longer checked in `toArray()`.
- **3.0.0**: in `fromArray()`, a property with `#[Ignore]` gets its default value; without a default it stays uninitialized.
- **3.0.1**: an int matches a `float` type: strict hydration of an int into a float property and an int default for `float $x = 0` are accepted.
- **3.0.4** (from 3.0.2): a relative DocBlock type such as `Enums\DeliveryCapabilityEnum` with `use App\SDK\Atlas\Enums;` resolves through the `use` alias of its first segment.

## Known issues

Present in every 3.x release:

- `ServiceTransformer::toArray()` returns only `['$classFQCN' => …]` — the result of the transformation is discarded. Fixed in 4.0.
- `toArray()` exports properties with `#[Ignore]`. Fixed in 4.0.
- `DefaultDTOTransformerFactory::default()` builds a new object graph on every call.

## Upgrade notes

1. **Boot the static API** at startup: `TransformerBootstrapper::boot()` (3.0.4+), or `DTOTransformer::boot(DefaultDTOTransformerFactory::default()->create())` and the same for `ServiceTransformer`. The traits (`ArrayConstructibleTrait`, `ArrayConvertibleTrait`, …) use the static API too. Without boot you get `NotInitializeException`.
2. **Removed from `DTOTransformer`**: `fromSmartArray()` (pass data with `'$className'` to `fromArray()`), `transformEnum()` (use `EnumConverter::toEnum()`) and all protected static helpers (`extractValue`, `checkAttributes`, `resolveValueForType`, …) — move custom logic into a hydrator or normalizer.
3. **Removed `Interfaces\IDTOFromSmartArrayTransformer`**; the replacement is `Interfaces\DTOFromSmartArrayTransformerInterface`.
4. **`BaseDTOFromArrayTransformer::transformFromArray()`** is `abstract public static` with a fifth parameter `array $context = []`. Overrides of `fromArray()` / `toArray()` must add `array $context = []`.
5. **Check the behaviour changes**: DateTime is exported as a string, an unknown enum value throws, a bad collection item is kept unless `C_STRICT`, `#[Ignore]` is not honoured in `toArray()`, nested `toArray()` methods are not called, nullable types are written `null|T`.
