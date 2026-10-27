[Українська](README-UA.md)

# 2.0 — smart arrays, DocBlock types, context-based `AttrDTO`, unit enums

Tags: 2.0.0, 2.0.1, 2.0.2, 2.0.3, 2.0.4, 2.0.5, 2.0.6. First released: 2025-10-03.

## Highlights

- **Smart arrays**: an exported object can carry `'$className'`, and `fromSmartArray()` builds the object back from it.
- **DocBlock types**: `@var` / `@param` such as `array<UserDto|DummyDTO|IntEnum>` build collections of DTOs and enums without attributes.
- **`#[AttrDTO]` takes a `context` array** instead of named flags.
- **Pure (`Unit`) enums** in hydration, export and JSON Schema; enums are exported as scalars.
- **`JsonSerializableTrait`**, and `SmartArrayConvertibleTrait` (2.0.5).

## What's new

### Smart arrays (2.0.0)

- New interface `Interfaces\IDTOFromSmartArrayTransformer::fromSmartArray(array $data, array $renameKey = [], array $namespaces = []): object`, implemented by `DTOTransformer`.
- The class is read from `$data['$className']`; its namespace comes from `$namespaces[<className>]` or `$namespaces['$defaultNamespace']`. Constants `BaseDTOFromArrayTransformer::DTO_CLASSNAME = '$className'` and `DTO_NS_KEY = '$defaultNamespace'`.
- `fromArray()` tries a smart array first; nested array values are tried as smart arrays too.
- `fromArray()` accepts a union of classes, `'A|B|C'`: it returns the first class that succeeds or throws `BadParamException` listing the error of each class. Short names are resolved through `$namespaces`.

### DocBlock types (2.0.0)

Property `@var` and constructor `@param` annotations are read. For an `array` member, the annotation (for example `array<UserDto|DummyDTO|IntEnum>`) creates `AttrDTO` instances automatically, so collection items become the matching DTO or enum; an item that fails to convert is kept as is. Constructor `@param` works from 2.0.1, `array<string, Dto>` maps from 2.0.2.

### `#[AttrDTO]` with `context` (2.0.0)

`AttrDTO(string $dtoFQCN, array $context = [])` with the keys `C_COLLECTION`, `C_RENAME_KEYS`, `C_TRANSFORMER`, `C_NS`, `C_IS_ENUM`, `C_PROPERTY` and the accessors `isCollection()`, `renameKeys()`, `getRenameKey()`, `transformerFQCN()`, `namespaces()`, `isEnum()`, `getContextByKey()`.

```php
// 1.4: #[AttrDTO(User::class, collection: true, renameKeys: ['randomNumber' => null])]
#[AttrDTO(User::class, context: [
    AttrDTO::C_COLLECTION => true,
    AttrDTO::C_RENAME_KEYS => ['randomNumber' => null],
])]
```

### Enums (2.0.0)

- New `DTOTransformer::transformEnum(string $enumFQCN, string|int $value): UnitEnum|string|int`: a backed enum uses `tryFrom` (otherwise `BadParamException('Invalid value "%s" for enum %s')`), a pure enum matches the case name case-insensitively and returns the raw value if nothing matches.
- `toArray()` exports a `BackedEnum` as `->value` and a pure enum as `->name`.
- `EnumResolver`: new `enumsDataFromSchema()`, `applyEnumFqcnToJsonSchema()`, `getEnumFQCN()`; `enumDataFromSchema()` got `int $resultKey = 0` and throws `NotSupportDTOException` when no enum is found; `schemaHasEnum()` walks nested `oneOf` / `items`.
- `TypeHintResolver`: new `typeWithNamespaceOrDefault()`, `applyToSchema()`, `filterSchema()`.
- `EnumVO::fromSchema()` maps JSON `integer` to `EnumResolver::INT` (was `STRING`).

### Traits

- **2.0.0** `JsonSerializableTrait`: `jsonSerialize(): string` returns `json_encode($this->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)` — a JSON string, not an array. `ArrayConvertibleTrait` uses it.
- **2.0.5** `SmartArrayConvertibleTrait`: `toArray()` exports with `'$className'`.

```php
class UserDto
{
    use SmartArrayConvertibleTrait;
}

$dto->toArray(); // contains '$className'
```

## Changes and fixes

- **2.0.0**: a public constructor is always called during hydration (in 1.4 only for classes with readonly properties). `DTOTransformer::isSupportClass()` returns `class_exists()`. `NotSupportDTOException` extends `BadParamException`. Short class names are resolved through the DTO's namespace instead of scanning its directory. `StringTransformerEnum::transformName()` keeps an all-uppercase input as is.
- **2.0.1**: constructor arguments are passed by name — fixes the argument order when a parameter is skipped by `#[Ignore]` or a rename to `null`. Constructor `@param` types apply (in 2.0.0 they were silently ignored).
- **2.0.2**: `TypeHintResolver::applyToSchema()` / `filterSchema()` walk `additionalProperties`, so `array<string, Dto>` resolves the item DTO or enum. New constants `TypeHintResolver::ADDITIONAL_PROPERTIES`, `CLASS_FQCN`.
- **2.0.3**: `#[AttrAssertions]` validates the field value (in 2.0.0–2.0.2 it validated the whole input array). A missing key is validated as `[]`.
- **2.0.4**: union members from JSON Schema are de-duplicated.
- **2.0.5**: `toArray()` no longer adds `'$className'` by default (`asSmartArray` default `true` → `false`); use `SmartArrayConvertibleTrait` or `asSmartArray: true`. `ServiceTransformer::toArray()` output loses `'$className'` and keeps `'$classFQCN'`.
- **2.0.6**: `EnumResolver::generateEnumSchema()` supports pure enums (`type: string`, `name => name`); before it failed on them.

## Upgrade notes

1. **Rewrite `#[AttrDTO(...)]`** to `context: [...]`; the public properties `collection`, `renameKeys`, `transformerFQCN` are gone.
2. **Custom transformers**: `IDTOFromArrayTransformer::fromArray()` and `BaseDTOFromArrayTransformer::transformFromArray()` got `array $namespaces = []`; subclasses of `BaseDTOFromArrayTransformer` must implement `fromSmartArray()`. Overrides of the protected `DTOTransformer` methods (`extractValue`, `checkAttributes`, `convertValue`, `checkEnum`) must follow the new signatures.
3. **Enums are exported as their value or name.**
4. **`catch (BadParamException)` also catches `NotSupportDTOException`.**
5. **Constructors are always called** during hydration.
6. Use 2.0.3+ for correct `#[AttrAssertions]`, and 2.0.5+ to get `toArray()` without `'$className'`.
