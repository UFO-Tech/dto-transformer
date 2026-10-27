[Українська](README-UA.md)

# 1.4 — `EnumResolver`, `EnumVO`, `StringTransformerEnum`

Tags: 1.4.0. First released: 2025-09-18.

## Highlights

- `Helpers\EnumResolver` replaces `Helpers\EnumsHelper` and `Helpers\XUfoValuesEnum`.
- `VO\EnumVO` — enum description read from a JSON Schema.
- `StringTransformerEnum` turns arbitrary strings into enum case names. `ext-intl` is now required.

## What's new

### `EnumResolver`

`Ufo\DTO\Helpers\EnumResolver` (string-backed enum, cases `STRING`, `INT`; constants `ENUM = 'x-ufo-enum'`, `ASSERTIONS`, `ENUM_NAME = 'name'`, `ENUM_KEY = 'enum'`, `METHOD_*`):

- `generateEnumSchema(string $enumFQCN, string $methodGetValues = 'values', string $methodTryFrom = 'tryFromValue'): array` — same output as 1.3.5. It now calls exactly the given methods (before it always called `values()` and `tryFromValue()`) and throws `InvalidArgumentException` when the try-method returns `null`.
- `findEnumNameInJsonSchema(array $type): ?string`
- `schemaHasEnum(array $schema): bool` — checks the root, `items` and `oneOf`.
- `enumDataFromSchema(array $schema, ?string $paramName = null): EnumVO`

```php
EnumResolver::generateEnumSchema(StringEnum::class);
// ['type' => 'string', 'x-ufo-enum' => ['name' => 'StringEnum', 'values' => ['A' => 'a', 'B' => 'b', 'C' => 'c']], 'enum' => ['a', 'b', 'c']]

$vo = EnumResolver::enumDataFromSchema($schema, 'status'); // $vo->name, $vo->type, $vo->values
```

### `EnumVO` and `StringTransformerEnum`

- `Ufo\DTO\VO\EnumVO` (readonly: `name`, `EnumResolver $type`, `array $values`). `fromSchema()` takes the name from `x-ufo-enum.name`, or `ucfirst($paramName) . 'Enum'`. The type is `EnumResolver::tryFrom(<schema type>) ?? STRING`, so JSON `integer` becomes `STRING` (mapped correctly from 2.0.0).
- `Ufo\DTO\StringTransformerEnum`: `transformName()` transliterates, adds `_` before inner capitals, upper-cases and prefixes `case_` before a leading digit; `alias()` maps `''` and `'--'` to `EMPTY`; `transliterate()` uses `Transliterator` (`ext-intl`).

## Changes and fixes

- `TypeHintResolver::jsonSchemaToPhp()` recognises an enum schema by `x-ufo-enum.name` alone (before it needed both `enum` and `x-ufo-enum`).

## Upgrade notes

1. `EnumsHelper::generateEnumSchema()` → `EnumResolver::generateEnumSchema()`; the second parameter changed meaning.
2. `XUfoValuesEnum::*` → `EnumResolver::*` constants (strings, not enum cases; `X_METHOD` is gone).
3. `ext-intl` is required.
