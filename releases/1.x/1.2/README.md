[Українська](README-UA.md)

# 1.2 — union types, enforced assertions

Tags: 1.2.0, 1.2.1, 1.2.2, 1.2.3. First released: 2025-07-13.

## Highlights

- **Union-typed properties and parameters**: an array value is matched against each class of the union.
- **`#[AttrAssertions]` is enforced**: a validation failure in `fromArray()` now throws `BadParamException` (before it was swallowed).
- An array that fits no class-typed property throws instead of being passed through.

## What's new

### Union types (1.2.0)

For a union type, an array value is tried against each class member. A class is used only when the array has all its required constructor parameters or uninitialized property keys. If no class matches and the union contains `array`, the raw array is kept; otherwise `BadParamException("Cannot assign array to property X::$p of type T")` is thrown.

```php
class ItemDTO
{
    public function __construct(public UserDto|DummyDTO $friend) {}
}

DTOTransformer::fromArray(ItemDTO::class, ['friend' => [/* keys of UserDto or DummyDTO */]]);
```

New protected methods in `DTOTransformer`: `resolveValueForType()`, `tryTransformToMatchingClass()`, `doesArrayMatchClass()`, `constructorParamsMatch()`, `propertiesMatch()`, `isKeyMissing()`, `createUninitializedInstance()`.

## Changes and fixes

- **1.2.0**: attribute errors are no longer swallowed — only `\ValueError` (an unsupported attribute) is ignored, so `AttrAssertions` and `AttrDTO` errors reach the caller as `BadParamException`. In 1.2.0–1.2.2 only the first supported attribute of a member is applied.
- **1.2.0**: the default value of a missing key no longer fails for a class without a constructor.
- **1.2.1**: `typeDescriptionToJsonSchema()` sets `classFQCN` to the type itself when it is not in `$classes` but the class exists.
- **1.2.2**: a property typed plain `array` takes an array as is again — in 1.2.0 and 1.2.1 it threw `BadParamException`. The `array` member of a union only allows a raw array and is no longer tried as a candidate.
- **1.2.3**: all attributes of a member are applied again, in order; the result still goes through union/class resolution.

## Upgrade notes

1. **Validation errors now throw.** `#[AttrAssertions]` failures during `fromArray()` raise `BadParamException`; up to 1.1.1 they were ignored.
2. **Arrays that fit no class-typed property throw** `BadParamException` instead of being passed through.
3. Use 1.2.2 or later for DTOs with plain `array` properties, and 1.2.3 or later for members with several attributes.
