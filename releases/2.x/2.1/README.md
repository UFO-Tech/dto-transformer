[Українська](README-UA.md)

# 2.1 — public-only export, hydration without the constructor, `mixed`

Tags: 2.1.0, 2.1.1. First released: 2026-02-25.

## Highlights

- **`toArray()` exports only public properties** by default; `publicOnly: false` restores the old output.
- **Hydration no longer calls the constructor**: every property, readonly ones included, is set through reflection.
- **`$className` in smart arrays is the FQCN**; 2.1.1 reads both FQCN and short names.
- **`mixed` properties** accept arrays (2.1.1).

These are breaking changes despite the minor version.

## What's new

### `publicOnly` (2.1.0)

- `IArrayConvertible::toArray(bool $publicOnly = true): array` (was `toArray(): array`).
- `IDTOToArrayTransformer::toArray(object $dto, array $renameKey = [], bool $asSmartArray = false, bool $publicOnly = true): array`.
- The same parameter on `DTOTransformer::toArray()`, `ArrayConvertibleTrait`, `SmartArrayConvertibleTrait` and `ServiceTransformer::toArray()`. It is passed down to nested objects.
- `JsonSerializableTrait::isPublicOnly(): bool` (returns `true`) — override it to change what `jsonSerialize()` exports.

```php
$dto->toArray();                    // public properties only
$dto->toArray(publicOnly: false);   // private and protected too, as before 2.1
```

## Changes and fixes

- **2.1.0**: `fromArray()` creates the object with `newInstanceWithoutConstructor()` and sets all properties through reflection; constructor code no longer runs. A missing required key reports `Missing required key for property: '<key>'` (was `... for constructor param: ...`). The protected `checkReadonlyInClass()` is removed.
- **2.1.0**: smart arrays write the FQCN to `'$className'` (was the short name). An array whose first item has `'$className'` is hydrated item by item; an item that fails stays raw.
- **2.1.0**: `ServiceTransformer::fromArray()` passes `$namespaces` on; `ServiceTransformer::toArray()` passes `$asSmartArray` and `$publicOnly`, so with its default `asSmartArray = true` the output contains `'$className'` again, next to `'$classFQCN'`.
- **2.1.1**: a `mixed` member takes an array as is (before: `BadParamException("Cannot assign array to property ...")`).
- **2.1.1**: `fromSmartArray()` accepts an FQCN in `'$className'` and falls back to namespace resolution, so 2.1.0 output can be read back.

## Upgrade notes

1. **Update `toArray` signatures** in classes implementing `IArrayConvertible` or `IDTOToArrayTransformer`; otherwise PHP raises an incompatible-declaration error.
2. **Private and protected properties disappear from `toArray()`** — pass `publicOnly: false` if you need them.
3. **Constructors are not called in `fromArray()`.** Values computed in the constructor must come in the input data.
4. **Smart-array `'$className'` is the FQCN** — use 2.1.1 or later to read it back.
