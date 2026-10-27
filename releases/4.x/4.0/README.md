[Українська](README-UA.md)

# 4.0 — new namespaces, class schemas, injected validator, hydration fixes

Not released yet.

## Highlights

- **New namespace layout.** Interfaces left `Ufo\DTO\Interfaces`, value objects left `Ufo\DTO\VO`; every class now lives next to the layer that uses it: `Contract`, `Factory`, `Schema` (`Enum`, `Meta`, `Type`), `Transformer` (`Context`, `Converter`, `Hydrator`, `Normalizer`). Hydrators and normalizers got consistent names (`ParamHydrator` → `HydratorChain`, `PropertyNormalizer` → `NormalizerChain`, `DtoHydrator` → `DTOHydrator`, …).
- **21 old names stay as deprecated aliases** until 4.1: the `Interfaces\I*` contracts, `VO\EnumVO`, `Helpers\TypeHintResolver`, `Helpers\EnumResolver`, the transformer and factory interfaces, the hydrator and normalizer extension points (`ParamHydratorInterface`, `AbstractParamHydrator`, `PropertyNormalizerInterface`, …) and `VO\TransformationContext` / `VO\NormalizationContext`. All other moved names have no alias.
- **Class schemas.** Hydration reads a per-class schema (properties, types, default-value strategy) built once and kept in the runtime and persistent caches. Every in-process cache is bounded.
- **The validator is a dependency**: `DefaultDTOTransformerFactory::default(validator: …)` instead of a new `Validation::createValidator()` on every `#[AttrAssertions]` check.
- **`default()` signature changed**: `default(?CacheInterface $persistentCache = null, int $runtimeCacheLimit = 2048, ?ValidatorInterface $validator = null)` — `bool $recreate` is gone.
- **Fixes**: `ServiceTransformer::toArray()` returns the whole array, `toArray()` honours `#[Ignore]`, `renameKey` falls back to default values, `#[AttrAssertions]` checks only present keys, `#[AttrDTO]` accepts `null`, `?DateTimeInterface` keeps `null`, object and enum constructor defaults work, `Validator::throw()` honours its argument.

## What's new

### Namespace layout

| Namespace | What lives there |
|---|---|
| `Ufo\DTO\Contract` | Interfaces your DTOs and static transformers implement |
| `Ufo\DTO\Factory` | Transformer and normalizer factories with their interfaces |
| `Ufo\DTO\Schema\Enum` | `EnumDefinition`, `EnumResolver`, `StringTransformerEnum` |
| `Ufo\DTO\Schema\Meta` | Reflection and docblock metadata, strict mode |
| `Ufo\DTO\Schema\Type` | `TypeHintResolver`, type schema resolution |
| `Ufo\DTO\Transformer\Context` | `TransformationContext`, `NormalizationContext`, `TransformKey` |
| `Ufo\DTO\Transformer\Converter` | Value converters |
| `Ufo\DTO\Transformer\Hydrator` | Hydrators (array → object) and their traits |
| `Ufo\DTO\Transformer\Normalizer` | Normalizers (object → array) |

`Ufo\DTO\Helpers` keeps only general utilities: `Validator`, `BoundedArray` and the new `StringListPool`. The facades in the root namespace (`DTOTransformer`, `ServiceTransformer`, `BaseDTOFromArrayTransformer`, …), `Attributes`, `Exceptions` and the root traits keep their names.

### Moved and renamed classes

All names are relative to `Ufo\DTO\`. "Alias" — the 3.x name still resolves to the new type in 4.0.

| 3.x name | 4.0 name | Alias |
|---|---|---|
| `Interfaces\IArrayConstructible` | `Contract\ArrayConstructibleInterface` | yes |
| `Interfaces\IArrayConvertible` | `Contract\ArrayConvertibleInterface` | yes |
| `Interfaces\IDTOFromArrayTransformer` | `Contract\StaticDTOFromArrayTransformerInterface` | yes |
| `Interfaces\IDTOToArrayTransformer` | `Contract\StaticDTOToArrayTransformerInterface` | yes |
| `VO\EnumVO` | `Schema\Enum\EnumDefinition` | yes |
| `Helpers\EnumResolver` | `Schema\Enum\EnumResolver` | yes |
| `Helpers\TypeHintResolver` | `Schema\Type\TypeHintResolver` | yes |
| `StringTransformerEnum` | `Schema\Enum\StringTransformerEnum` | no |
| `DTOAttributesEnum` | replaced by `Transformer\Attribute\AssertionsValidator` and `Transformer\Attribute\AttrDTOResolver` | no |
| `Interfaces\DTOFromArrayTransformerInterface` | `Transformer\DTOFromArrayTransformerInterface` | yes |
| `Interfaces\DTOFromSmartArrayTransformerInterface` | `Transformer\DTOFromSmartArrayTransformerInterface` | no |
| `Interfaces\DTOToArrayTransformerInterface` | `Transformer\DTOToArrayTransformerInterface` | yes |
| `Interfaces\Converter\DateTimeValueConverterInterface` | `Transformer\Converter\DateTimeValueConverterInterface` | no |
| `Interfaces\Factory\DTOTransformerFactoryInterface` | `Factory\DTOTransformerFactoryInterface` | yes |
| `Interfaces\Factory\DTOTransformerFromArrayFactoryInterface` | `Factory\DTOTransformerFromArrayFactoryInterface` | yes |
| `Interfaces\Factory\DTOTransformerToArrayFactoryInterface` | `Factory\DTOTransformerToArrayFactoryInterface` | yes |
| `Interfaces\Factory\PropertyNormalizerFactoryInterface` | `Factory\NormalizerChainFactoryInterface` | no |
| `Factory\DefaultPropertyNormalizerFactory` | `Factory\DefaultNormalizerChainFactory` | no |
| `Interfaces\Hydrator\ParamHydratorInterface` | `Transformer\Hydrator\HydratorInterface` | yes |
| `Interfaces\Hydrator\ParamHydratorChainInterface` | `Transformer\Hydrator\HydratorChainInterface` | yes |
| `Transformer\Hydrator\ParamHydrator` | `Transformer\Hydrator\HydratorChain` | no |
| `Transformer\Hydrator\AbstractParamHydrator` | `Transformer\Hydrator\AbstractHydrator` | yes |
| `Transformer\Hydrator\DtoHydrator` | `Transformer\Hydrator\DTOHydrator` | no |
| `Transformer\Hydrator\EnumParamHydrator` | `Transformer\Hydrator\EnumHydrator` | no |
| `Transformer\Hydrator\ScalarParamHydrator` | `Transformer\Hydrator\ScalarHydrator` | no |
| `Transformer\Hydrator\UnionParamHydrator` | `Transformer\Hydrator\UnionHydrator` | no |
| `Transformer\Traits\PayloadGuardTrait` | `Transformer\Hydrator\PayloadGuardTrait` | no |
| `Transformer\Traits\ValueExtractorTrait` | `Transformer\Hydrator\ValueExtractorTrait` | no |
| `Interfaces\Normalizer\PropertyNormalizerInterface` | `Transformer\Normalizer\NormalizerInterface` | yes |
| `Interfaces\Normalizer\PropertyNormalizerChainInterface` | `Transformer\Normalizer\NormalizerChainInterface` | yes |
| `Interfaces\Normalizer\DTONormalizerInterface` | `Transformer\Normalizer\DTONormalizerInterface` | no |
| `Interfaces\Normalizer\SerializationContextProviderInterface` | `Schema\Meta\SerializationContextAttributeReaderInterface` | no |
| `Transformer\Normalizer\PropertyNormalizer` | `Transformer\Normalizer\NormalizerChain` | no |
| `Transformer\Normalizer\DtoNormalizer` | `Transformer\Normalizer\DTONormalizer` | no |
| `Transformer\Normalizer\ScalarValueConverter` | `Transformer\Normalizer\ScalarNormalizer` | no |
| `Transformer\Metadata\AttributeSerializationContextProvider` | `Schema\Meta\SerializationContextAttributeReader` | no |
| `VO\TransformationContext` | `Transformer\Context\TransformationContext` | yes |
| `VO\NormalizationContext` | `Transformer\Context\NormalizationContext` | yes |
| `VO\TransformKeyVO` | `Transformer\Context\TransformKey` | no |
| `Interfaces\Meta\DocBlockParserInterface` | `Schema\Meta\DocBlockParserInterface` | no |
| `Interfaces\Meta\ReflectionMetadataProviderInterface` | `Schema\Meta\ReflectionMetadataProviderInterface` | no |
| `Interfaces\Meta\StrictModeResolverInterface` | `Schema\Meta\StrictModeResolverInterface` | no |
| `Interfaces\Meta\TypeSchemaResolverInterface` | `Schema\Type\TypeSchemaResolverInterface` | no |
| `Transformer\Metadata\DocBlockMetadata` | `Schema\Meta\DocBlockMetadata` | no |
| `Transformer\Metadata\PhpDocumentorDocBlockParser` | `Schema\Meta\PhpDocumentorDocBlockParser` | no |
| `Transformer\Metadata\ReflectionMetadataKeyGenerator` | `Schema\Meta\ReflectionMetadataKeyGenerator` | no |
| `Transformer\Metadata\ReflectionMetadataProvider` | `Schema\Meta\ReflectionMetadataProvider` | no |
| `Transformer\Metadata\RuntimeReflectionCache` | `Schema\Meta\RuntimeReflectionCache` | no |
| `Transformer\Metadata\StrictModeResolver` | `Schema\Meta\StrictModeResolver` | no |
| `Transformer\Type\ReflectionTypeSchemaResolver` | `Schema\Type\ReflectionTypeSchemaResolver` | no |
| `VO\ResolvedTypeSchemaVO` | `Schema\Type\ResolvedTypeSchema` | no |

`Interfaces\ArrayConstructibleInterface` and `Interfaces\ArrayConvertibleInterface` are also aliases of the `Contract\…` interfaces; these names were never in a release, only in development commits.

Removed without a replacement: `Annotations\StrictModeTag`, `Interfaces\Factory\ParamHydratorFactoryInterface`, `Interfaces\Meta\ReflectionMetadataKeyGeneratorInterface`, `Interfaces\Meta\RuntimeReflectionCacheInterface`, `Transformer\Metadata\ClassMetadata`, `Transformer\Metadata\ParameterMetadata`, `Transformer\Metadata\PropertyMetadata`.

### Deprecated aliases

`aliases.php` (Composer `autoload.files`) registers a `class_alias` for each of the 21 names on every request, so an old name is the same type as the new one: `instanceof`, `catch`, parameter types and `is_a()` work in both directions, and a DTO that implements `IArrayConvertible` passes a check against `ArrayConvertibleInterface`.

There is no runtime deprecation notice. The deprecation is visible in the IDE through `aliases-stubs.php`, which is never loaded.

```php
use Ufo\DTO\Contract\ArrayConvertibleInterface;
use Ufo\DTO\Interfaces\IArrayConvertible; // deprecated, removed in 4.1

$dto instanceof ArrayConvertibleInterface === $dto instanceof IArrayConvertible; // true
```

### Factory and validator

`DefaultDTOTransformerFactory::default(?CacheInterface $persistentCache = null, int $runtimeCacheLimit = RuntimeReflectionCache::DEFAULT_LIMIT, ?ValidatorInterface $validator = null)`:

- `$runtimeCacheLimit` — entries in `RuntimeReflectionCache` (2048 by default, `0` — unbounded).
- `$validator` — the Symfony validator for `#[AttrAssertions]`. Without it the factory builds one lazy `Validation::createValidator()` shared by all its transformers, on the first check.
- Every `default()` call builds a new object graph with new caches; keep the factory (or the transformer) yourself. `create()` reuses the inner transformers of the factory, so `DTOTransformer` and `ServiceTransformer` created from one factory share caches.

```php
$factory = DefaultDTOTransformerFactory::default($cache, validator: $appValidator); // once per process
DTOTransformer::boot($factory->create());
ServiceTransformer::boot($factory->create(ServiceTransformer::class));
```

`Helpers\Validator::validate()` takes an optional fourth parameter `?ValidatorInterface $validator`.

### Attribute handling

`DTOAttributesEnum` (an enum used as a service with three static caches) is replaced by two services owned by `DTOFromArrayTransformer` and built by `DefaultDTOTransformerFromArrayFactory`:

- `Transformer\Attribute\AssertionsValidator(ValidatorInterface)` — `validate(AttrAssertions, $value, $reflection)`; the validator is always the injected one.
- `Transformer\Attribute\AttrDTOResolver` — `resolve(AttrDTO, $value, $reflection, DTOFromContextTransformerInterface, TransformationContext)`: `null`, one DTO or enum, collection, strict mode, custom transformer.

`PropertySchema::$attrDto` holds the `AttrDTO` instance (was `ReflectionAttribute`), like `$assertions`, so attribute instances are built once with the class schema and need no cache. Subclasses of `AttrDTO` are recognised by `instanceof`.

### Class schemas and caching

- `fromArray()` and `toArray()` work from one `Schema\ClassSchema` / `Schema\PropertySchema`, built by `Schema\ClassSchemaFactory` and provided by `Schema\ClassSchemaProviderInterface` (`ClassSchemaProvider`, cached by `Schema\Cache\CachedClassSchemaProvider`). `Factory\DefaultSchemaFactory::createClassSchemaProvider()` builds it once, and `default()` passes the same provider to both directions. The default-value strategy of a property is fixed once per class: property default, same-named optional constructor parameter, missing, or the per-call path for static and hooked properties.
- The schema layer does not depend on hydrators. `ClassSchemaProviderInterface::typeSchemas()` returns `Schema\Type\ResolvedTypeSchema`; the hydration side compiles them into nodes of its chain: `Transformer\Hydrator\CompiledTypeSchemaProviderInterface` (`CompiledTypeSchemaProvider`, cached by `Transformer\Hydrator\Cache\CachedCompiledTypeSchemaProvider`).
- `ClassSchema::$exportedProperties` / `$publicExportedProperties` — the `PropertySchema`s `toArray()` exports (without `#[Ignore]`); `PropertySchema::$serializationContext` — the values of `#[SerializationContext]`, read once with the schema; `ClassSchema::$requiredKeys` — the keys without which hydration certainly fails (used by `RequiredKeysCandidateFilter`). `DTONormalizer` and the filter keep no class caches of their own.
- Base classes hold no caches; caching lives in `Cached*` decorators:

  | Base class | Cache |
  |---|---|
  | `Schema\ClassSchemaFactory` (`ClassSchemaDescriberInterface`) | `Schema\Cache\CachedClassSchemaDescriber` — persistent cache of the descriptions |
  | `Schema\ClassSchemaProvider` | `Schema\Cache\CachedClassSchemaProvider` — schemas in process memory |
  | `Transformer\Hydrator\CompiledTypeSchemaProvider` | `Transformer\Hydrator\Cache\CachedCompiledTypeSchemaProvider` |
  | `Transformer\Hydrator\HydratorChain` | `Transformer\Hydrator\Cache\CachedHydratorChain` — compiled nodes |

  A stale description in the persistent cache (the class changed) is rebuilt and overwritten.
- The persistent cache stores class schema descriptions (property defaults included) and per-class type schemas. Type schemas are keyed by the namespace keys the class actually reads, so classes with native types do not depend on the caller's `$namespaces`.
- Caching moved into decorators: `Schema\Meta\Cache\CachedReflectionMetadataProvider`, `CachedStrictModeResolver`, `CachedDocBlockParser`. `ReflectionMetadataProvider`, `StrictModeResolver`, `PhpDocumentorDocBlockParser` and `ReflectionTypeSchemaResolver` are uncached and take fewer constructor arguments.
- Every in-process cache is bounded: class schemas 3000, extra type-schema sets per class 32, hydrator nodes 4096, namespace key sets 1024 (`Helpers\StringListPool`, one array per equal set), `RuntimeReflectionCache` — `$runtimeCacheLimit`. In 3.1.0 the hydrator cache and the default-key cache had no limit.
- Instances of `#[AttrDTO]`, `#[AttrAssertions]` and `#[SerializationContext]` are created once per attribute and kept in a `WeakMap`.
- Schemas built from a payload's `'$className'` are not cached.

### Extension points

- `Transformer\DTOFromContextTransformerInterface::transformFromContext()` — a transformer for nested DTOs gets the `TransformationContext` object instead of an array.
- `Transformer\DTOCandidateFilterInterface::rejectsData()` — tells that hydration of a class certainly fails, so `fromArray('A|B')` and union properties try that class last. `BaseDTOFromArrayTransformer::rejectsData()` is a new protected static hook (`false` by default); `DTOTransformer` delegates it to the inner transformer.
- `Transformer\Hydrator\SchemaNodeHydratorInterface::resolveNode()` with `Schema\SchemaNode` — a hydrator for nested schemas (unions, items, `additionalProperties`) gets the pre-built node of the inner schema.
- `DefaultDTOTransformerFromArrayFactory`: protected `createTypeSchemaProvider()`, `createTransformer()`, `createHydrators()`.
- `DefaultSchemaFactory`: `createClassSchemaProvider()`, `createDefaultValueResolver()`, protected `createClassSchemaFactory()`.
- `Schema\ClassSchemaDescriberInterface` — where schema descriptions come from; `ClassSchemaProvider(ClassSchemaDescriberInterface, ClassSchemaFactory)`.
- `DefaultMetadataFactory`: new `createMetadataProvider()`, `createStrictModeResolver()`; `createRuntimeReflectionCache()` takes `int $limit`.
- `Transformer\SmartClassResolver`, `Schema\DefaultValueResolver`, `Schema\RequiredKeysCandidateFilter`, `Schema\Type\TypeCompatibility` are constructor dependencies of `DTOFromArrayTransformer` and can be replaced.

## Changes and fixes

- **`toArray()` honours `#[Ignore]`.** In 3.x properties with Symfony `#[Ignore]` were exported.
- **`ServiceTransformer::toArray()`** returns the whole array plus `'$classFQCN'` and passes `$context` on. In 3.x it returned only `['$classFQCN' => …]`.
- **`renameKey` in `fromArray()`.** Payload keys are renamed to property names first, then the usual path runs:
  - a missing renamed key falls back to the property or constructor default (3.1.0: "Missing required key" even with a default);
  - an ignored property keeps its default even when the renamed key is in the payload (3.1.0: the payload value won);
  - a rename to `null` treats the key as missing (3.1.0: `TypeError`).
- **`#[AttrAssertions]` checks only present keys.** A missing key goes to the default value or to "Missing required key"; an explicit `null` is validated as `null`. In 3.1.0 both were validated as `[]`.
- **`#[AttrDTO]` with `null`**: `null` for a nullable or untyped property, otherwise `BadParamException('Cannot assign null to non-nullable …')`. In 3.1.0: `TypeError` for a single DTO, a PHP warning and `[]` for a collection. A non-iterable value for a collection throws `BadParamException` in strict mode and is kept as is otherwise (3.1.0: warning and `[]`).
- **`\ValueError` from attribute processing** (for example from a nested transformer) keeps its message. In 3.1.0 any `ValueError` became `Unsupported attribute type`.
- **Custom transformer of `#[AttrDTO]`** (`AttrDTO::C_TRANSFORMER`):
  - a class that does not implement `StaticDTOFromArrayTransformerInterface` throws `NotSupportDTOException('Transformer … must implement …')`; in 3.x it was silently replaced by the default transformer;
  - "is not support transform for …" names the custom transformer; in 3.x it named the default one;
  - it receives the namespaces (caller's and `AttrDTO::C_NS`) as the fourth argument of `fromArray()`; in 3.x it got only `$renameKey`.
- **`?DateTimeInterface` with `null`** stays `null` (3.1.0: the current time). A union with `null` returns `null` for `null` before trying other branches. A non-nullable date with `null` is still the current time.
- **Object and enum constructor defaults** (`Inner $x = new Inner()`, `Status $s = Status::A`) are used for a missing key. In 3.1.0 this threw `Error: Call to a member function matchType() on null`.
- **`RuntimeReflectionCache` limit `0`** means unbounded (3.1.0: no entries kept).
- A hydrator result of `null` falls back to the raw value on every call (3.1.0: only on cache hits).
- `TransformationContext::withNamespaces()`, `withStrict()`, `withContext()` return the same object when nothing changes.
- **`Validator::throw(string $class = BadParamException::class)`** ignored `$class` and always threw `BadParamException` (3.1.0 included). It now throws `new $class($message)` with the current error as the message; called without an argument it still throws `BadParamException`.

```php
use Ufo\DTO\Helpers\Validator;
use Symfony\Component\Validator\Constraints\NotBlank;

Validator::validate('', [new NotBlank()])->throw(DomainException::class); // DomainException
Validator::validate('', [new NotBlank()])->throw();                       // BadParamException
```

### Removed

- From `Transformer\DTOFromArrayTransformer`: protected `resolveClassForData()`, `resolveSmartClass()` (→ `SmartClassResolver`), `extractValue()`, `resolveValue()` (→ `resolvePropertyValue()`, `resolveWithSchema()`), `getPropertyValue()`, `canAssignToParameter()` (→ `DefaultValueResolver`, `TypeCompatibility`), `getPromotedConstructorParameter()`, `getPropertyKey()`, `propertyKey()`, `createUninitializedInstance()`, `reflectionClass()`, `reflectionProperties()` and the `$defaultPropertyKeys` property.
- From `Schema\Meta\ReflectionMetadataProviderInterface`: `classMetadata()`, `properties()`, `propertyMetadata()`, `parameterMetadata()`, `promotedConstructorParameter()`; added `constructorParameters()`.
- From `Schema\Meta\ReflectionMetadataKeyGenerator`: `namespacesKey()`, `reflectionSchemaKey()`, `nativeTypeKey()`; added `reflectionKey()`, `declaringClassKey()`.
- `DefaultDTOTransformerFactory::$defaultInstance` (it was never assigned, so `default()` already built a new graph on every call).

### Constructor changes

| Class | 3.1.0 | 4.0 |
|---|---|---|
| `DefaultDTOTransformerFromArrayFactory` | `(typeSchemaResolver, metadataProvider, keyGenerator)` | `(typeSchemaResolver, metadataProvider, ClassSchemaProviderInterface $schemaProvider, ValidatorInterface $validator, DateTimeValueConverterInterface $dateTimeValueConverter)` — all required |
| `Transformer\DTOFromArrayTransformer` | `(hydratorChain, typeSchemaResolver, metadataProvider, keyGenerator)` | `(HydratorChainInterface, TypeSchemaResolverInterface, ClassSchemaProviderInterface, CompiledTypeSchemaProviderInterface, DefaultValueResolver, SmartClassResolver, DTOCandidateFilterInterface, AssertionsValidator, AttrDTOResolver)` |
| `Factory\DefaultNormalizerChainFactory` | `DefaultPropertyNormalizerFactory(metadataProvider, contextProvider, converter)` | `(ClassSchemaProviderInterface $schemaProvider, DateTimeValueConverterInterface)` |
| `Transformer\Normalizer\DTONormalizer` | `DtoNormalizer(metadataProvider, contextProvider)` | `(ClassSchemaProviderInterface $schemaProvider, PropertyContextCache = new PropertyContextCache())` |
| `Schema\ClassSchemaFactory` | — (new in 4.0) | `(ReflectionMetadataProviderInterface, TypeSchemaResolverInterface, DefaultValueResolver, NamespaceKeyAnalyzer, SerializationContextAttributeReaderInterface)` |
| `Schema\Type\ReflectionTypeSchemaResolver` | `(strictModeResolver, metadataProvider, keyGenerator, runtimeCache)` | `(strictModeResolver, metadataProvider)` |
| `Schema\Meta\StrictModeResolver` | `(metadataProvider, keyGenerator, runtimeCache)` | `(metadataProvider)` |
| `Schema\Meta\ReflectionMetadataProvider` | `(docBlockParser, keyGenerator, runtimeCache)` | `(docBlockParser)` |
| `Schema\Meta\PhpDocumentorDocBlockParser` | `(factory, runtimeCache)` | `(factory)` |
| `Transformer\Hydrator\DateTimeHydrator`, `Transformer\Normalizer\DateTimeNormalizer` | `(?DateTimeValueConverterInterface = null)` | converter required |

`Schema\Type\TypeSchemaResolverInterface` requires two more methods: `source()` and `fromSource()`.

`SerializationContextProviderInterface` became `Schema\Meta\SerializationContextAttributeReaderInterface` (implementation `SerializationContextAttributeReader`, no cache): `contextForProperty(ReflectionProperty, NormalizationContext): NormalizationContext` is replaced by `read(ReflectionProperty): array` — the reader returns only the values of the property's `#[SerializationContext]`. `ClassSchemaFactory` calls it once per property and keeps the result in `PropertySchema::$serializationContext`; `DTONormalizer` builds the context from it with the new `NormalizationContext::forPropertyValue()`. A custom reader goes to `DefaultSchemaFactory::createClassSchemaFactory()`, not to the normalizer. `DTONormalizer` takes an optional second argument `Transformer\Normalizer\Cache\PropertyContextCache`: contexts of the properties of a class are built once per parent context, so the items of a collection share them.

### Composer

- `autoload.files: ["aliases.php"]` registers the deprecated aliases.
- `require` is the same as in 3.1.0. The code uses PHP 8.4 APIs (`ReflectionClass::newLazyProxy()`, `array_any()`, `array_all()`).

## Upgrade notes

1. **Move to the 4.0 names** (table above). Names without an alias fail at once — mostly custom hydrators, normalizers, factories and manual service wiring of `DTOTransformer`. In Symfony, service ids and autowiring aliases that use 3.x interface names must be renamed.
2. **The 21 aliases are removed in 4.1.** A constraint like `^4.0` will pick up 4.1 automatically, so switch to the new names before 4.1 is released.
3. **String checks return the new name.** `get_class()`, `class_implements()` and `ReflectionClass::getName()` give the 4.0 name, so `isset(class_implements($dto)['Ufo\DTO\Interfaces\IArrayConvertible'])` is `false`, and `$name === IArrayConstructible::class` no longer matches a reflected 4.0 type.
4. **`default($cache, true)`**: drop the second argument and keep the factory yourself; pass `runtimeCacheLimit:` and `validator:` by name. With `strict_types` the old call is a `TypeError`; without it `true` silently becomes a cache limit of 1.
5. **Manual wiring**: pass the new constructor arguments (table above). For `DefaultDTOTransformerFromArrayFactory` — a `ClassSchemaProviderInterface` (from `DefaultSchemaFactory::createClassSchemaProvider()`, the same one for `DefaultNormalizerChainFactory`), `validator`, `dateTimeValueConverter` instead of `keyGenerator`; the persistent cache now goes to `createClassSchemaProvider()`; for `DateTimeHydrator` / `DateTimeNormalizer` — `new DateTimeConverter()`. A chain built by hand as `new HydratorChain()` (3.x `ParamHydrator`) compiles schemas on every call — use `CachedHydratorChain` for the 3.x caching. Prefer `DefaultMetadataFactory` and `DefaultDTOTransformerFromArrayFactory::create()` to calling the constructors.
6. **Custom implementations**: `TypeSchemaResolverInterface` needs `source()` and `fromSource()`; `ReflectionMetadataProviderInterface` needs `constructorParameters()`; `SerializationContextProviderInterface` becomes `Schema\Meta\SerializationContextAttributeReaderInterface` with `read()` returning the values instead of `contextForProperty()` returning a context; it is passed to `ClassSchemaFactory`. Type hints on the removed `RuntimeReflectionCacheInterface` / `ReflectionMetadataKeyGeneratorInterface` move to the concrete classes.
7. **Subclasses of `DTOFromArrayTransformer`** that use the removed protected methods: move the logic to `hydrateProperty()` / `resolvePropertyValue()`, or replace `DefaultValueResolver` / `SmartClassResolver` through `DefaultDTOTransformerFromArrayFactory::createTransformer()`.
8. **`new RuntimeReflectionCache(0)`** is now unbounded; use a small positive limit for a tiny cache.
9. **Clear the persistent cache on deploy.** Keys carry no class version, and class schemas with default values are stored there now.
10. **Check code and tests that depend on the old outcomes**: `toArray()` skips `#[Ignore]`, `ServiceTransformer::toArray()` returns the whole array, `?DateTime` with `null` is `null`, a nullable `#[AttrDTO]` collection with `null` is `null` (was `[]`), `#[AttrAssertions]` does not fire on missing keys, renamed missing keys use defaults, `ValueError` keeps its message.
11. **`DTOAttributesEnum` is gone**: code that called `tryFromAttr()` / `process()` uses `AttrDTOResolver::resolve()` / `AssertionsValidator::validate()` with a transformer instance (the static `DTOTransformer::class` form is not supported). Check every `AttrDTO::C_TRANSFORMER`: it must implement `StaticDTOFromArrayTransformerInterface`, otherwise hydration now fails.
11. **`Validator::throw()` honours its argument.** If you pass a class, that class is thrown now (before it was always `BadParamException`). Code that catches `BadParamException` around such a call must catch the passed class instead. The class must accept the message as its first constructor argument.
