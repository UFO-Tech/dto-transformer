[Українська](README-UA.md)

# 1.0 — DTO ⇄ array, attributes, `ServiceTransformer`

Tags: 1.0.0, 1.0.1, 1.0.2, 1.0.3. First released: 2025-05-19.

## Highlights

- **`DTOTransformer`**: two-way conversion between arrays and DTOs through the public constructor and properties, with readonly support, key renaming and Symfony `#[Ignore]`.
- **Attributes**: `#[AttrDTO]` for nested DTOs, collections and custom transformers; `#[AttrAssertions]` for Symfony Validator constraints.
- **`ServiceTransformer`**: writes and reads `'$classFQCN'` for polymorphic round-trips.
- **`TypeHintResolver`**: PHP type ⇄ JSON Schema helpers.

## What's new

### `DTOTransformer` (1.0.0)

`Ufo\DTO\DTOTransformer` extends `BaseDTOFromArrayTransformer` and implements `IDTOFromArrayTransformer` and `IDTOToArrayTransformer`:

- `fromArray(string $classFQCN, array $data, array $renameKey = []): object`
- `toArray(object $dto, array $renameKey = []): array`
- `isSupportClass(string $classFQCN): bool` — always `true`.

A class with readonly properties is created with its constructor (`newInstanceArgs`), any other class without the constructor, and its properties are then set. A key counts as present by `array_key_exists`, so an explicit `null` is used as a value. Properties typed with a class are hydrated from nested arrays. `$renameKey` maps a DTO name to a data key; `null` skips the field. In `toArray`, a nested object that implements `IArrayConvertible` is exported with its own `toArray()`, any other object recursively.

`BaseDTOFromArrayTransformer::fromArray()` throws `NotSupportDTOException` when `isSupportClass()` is `false` and wraps any `Throwable` from `transformFromArray()` into `BadParamException`.

Traits for DTOs: `ArrayConstructibleTrait::fromArray(array $data, array $renameKey = []): static` and `ArrayConvertibleTrait::toArray()`, with the interfaces `Interfaces\IArrayConstructible` and `Interfaces\IArrayConvertible`.

### Attributes (1.0.0)

- `Attributes\AttrDTO(string $dtoFQCN, bool $collection = false, array $renameKeys = [], ?string $transformerFQCN = null)` — nested DTO or collection of DTOs, optionally built by another transformer.
- `Attributes\AttrAssertions(array $assertions)` — Symfony `Constraint[]` for the value.

Both apply to properties, parameters and methods; subclasses of the attributes are recognised too. They are processed by `DTOAttributesEnum` (cases `ASSERTIONS`, `DTO`).

```php
use Symfony\Component\Validator\Constraints as Assert;
use Ufo\DTO\Attributes\AttrAssertions;
use Ufo\DTO\Attributes\AttrDTO;

class OrderDTO
{
    #[AttrAssertions([new Assert\Positive()])]
    public int $id;

    #[AttrDTO(ItemDTO::class, collection: true)]
    public array $items;
}
```

### `ServiceTransformer` (1.0.0)

`Ufo\DTO\ServiceTransformer extends DTOTransformer`: `toArray()` adds `'$classFQCN' => $dto::class`, and `fromArray()` uses `$data['$classFQCN']` as the target class when it is present.

### Helpers (1.0.0)

- `Helpers\Validator` — `validate()`, `getData()`, `getErrors()`, `hasErrors()`, `getCurrentError()`, `throw()`.
- `Helpers\TypeHintResolver` — type aliases (string-backed enum) with `normalize()`, `normalizeArray()`, `isRealClass()`, `jsonSchemaToPhp()` (with `oneOf`), `phpToJsonSchema()`, `mixedForJsonSchema()`.
- Exceptions `Exceptions\BadParamException` and `Exceptions\NotSupportDTOException`, both extending `\InvalidArgumentException`.

## Changes and fixes

- **1.0.1**: new case `TypeHintResolver::COLLECTION = 'collection'`; `normalize('collection')` returns `'array'` (was `'object'`). `phpToJsonSchema()` accepts only `string` (was `array|string`).
- **1.0.2**: `symfony/serializer` constraint `7.2.*` → `^7.2`.
- **1.0.3**: `TypeHintResolver::isRealClass()` returns `false` for enums; new `TypeHintResolver::isEnum(string $value): bool`.

In 1.0 a failed `#[AttrAssertions]` check is swallowed inside `DTOTransformer::fromArray()`: every attribute error is ignored. Validation errors are raised from 1.2.0.

## Upgrade notes

1. **1.0.1**: passing an array to `phpToJsonSchema()` now gives a `TypeError`.
2. **1.0.3**: code relying on `isRealClass(SomeEnum::class) === true` must use `isEnum()`.
