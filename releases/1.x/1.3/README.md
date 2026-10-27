[Українська](README-UA.md)

# 1.3 — backed enums, `$ref` and enum schemas

Tags: 1.3.0, 1.3.1, 1.3.2, 1.3.3, 1.3.4, 1.3.5, 1.3.6, 1.3.7. First released: 2025-08-21.

## Highlights

- **Backed enums in `fromArray()`**: a scalar becomes an enum case when the member is typed with a backed enum (1.3.0, reworked in 1.3.1).
- **`$ref` in JSON Schema** with a namespace map (1.3.2 – 1.3.7).
- **Enum JSON Schema** with the `x-ufo-enum` extension: `Helpers\EnumsHelper` and `Helpers\XUfoValuesEnum` (1.3.4, format changed in 1.3.5).

## What's new

### Backed enums (1.3.0, 1.3.1)

1.3.0 converted a scalar with `$enumFQCN::from($value)` for every present key. It read the type name unconditionally, so union-typed and untyped members failed with `BadParamException`. 1.3.1 moved the conversion into `DTOTransformer::checkEnum()`: it runs after the attributes, only for `string|int` values and a single (or nullable) backed-enum type, and on failure keeps the raw value.

### `$ref` and namespaces (1.3.2 – 1.3.7)

- **1.3.2**: `TypeHintResolver::jsonSchemaToPhp(array|string $type, ?string $namespace = null)` understands `$ref`: `'#/components/schemas/X'` → `$namespace . '\X'`. New constant `TypeHintResolver::REF = '$ref'`.
- **1.3.3**: `oneOf` members are resolved recursively as full schemas; with no namespace, `{type: object, additionalProperties}` → `'array'`.
- **1.3.6**: `$ref` without a namespace gives `'X'` (was `'\X'`); `jsonSchemaToTypeDescription()` passes the namespace into nested `oneOf`, `additionalProperties` and `items`.
- **1.3.7**: the namespace became a map: `jsonSchemaToPhp(array|string $type, array $namespaces = [])` and `jsonSchemaToTypeDescription(array $schema, array $namespaces = [])`. Keys are short type names; fallback keys are `'dto'` for `$ref` types and `'enum'` for enums. A schema with `enum` and `x-ufo-enum` maps back to the enum name.

```php
TypeHintResolver::jsonSchemaToPhp(['$ref' => '#/Qqq'], ['Qqq' => 'DTO']);                // 'DTO\Qqq'
TypeHintResolver::jsonSchemaToTypeDescription($stringEnumSchema, ['enum' => 'DefaultNS']); // 'DefaultNS\StringEnum'
```

### Enum schema (1.3.4, 1.3.5)

`Helpers\EnumsHelper::generateEnumSchema(string $enumFQCN, string $method = 'values'): array` and the `Helpers\XUfoValuesEnum` keys (`x-ufo`, `x-ufo-enum`, `x-ufo-assertions`, `x-method`, …). `typeDescriptionToJsonSchema(EnumClass)` returns this schema. Format from 1.3.5:

```php
['type' => 'string', 'x-ufo-enum' => ['name' => 'StringEnum', 'values' => ['A' => 'a', 'B' => 'b']], 'enum' => ['a', 'b']]
```

In 1.3.4 the name was in a separate `x-ufo-enum-name` key and `x-ufo-enum` held only the values.

## Changes and fixes

- **1.3.4**: `jsonSchemaToPhp()` turns `oneOf` of T and null into `?T`; an object with `additionalProperties` and no `classFQCN` is `'array'` regardless of the namespace, so `jsonSchemaToTypeDescription()` describes `object[]` as `array[]`. `jsonSchemaToTypeDescription()` got the namespace parameter and supports `$ref`. `checkMixedInSchema()` accepts members without `type`.

## Upgrade notes

1. Skip 1.3.0: union-typed and untyped members fail there. Use 1.3.1 or later.
2. The enum schema format differs between 1.3.4 and 1.3.5+.
3. Object schemas without `classFQCN` describe as `array` from 1.3.4.
4. A `$ref` without a namespace lost its leading `\` in 1.3.6.
5. In 1.3.7 the `?string $namespace` parameter became `array $namespaces`.
