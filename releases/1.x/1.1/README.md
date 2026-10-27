[Українська](README-UA.md)

# 1.1 — PHPDoc types ⇄ JSON Schema

Tags: 1.1.0, 1.1.1. First released: 2025-07-09.

## Highlights

- `TypeHintResolver::typeDescriptionToJsonSchema()` turns a PHPDoc type expression into JSON Schema; `jsonSchemaToTypeDescription()` does the reverse (1.1.1).
- `TypeHintResolver::getUsesNamespaces()` maps short class names to FQCNs.
- New dependencies: `phpdocumentor/type-resolver ^1.10`, `phpdocumentor/reflection-docblock ^5.6`.

## What's new

### PHPDoc type → JSON Schema (1.1.0)

`TypeHintResolver::typeDescriptionToJsonSchema(string $typeExpression, array $classes = []): array`:

| PHPDoc | JSON Schema |
|---|---|
| scalar | `{type}` |
| `T[]`, `array<int, T>` | `{type: array, items: <T>}` |
| `array<string, T>` | `{type: object, additionalProperties: <T>}` |
| `A\|B`, `?T` | `oneOf` |
| `mixed` | `oneOf` of the six basic types |
| class | `{type: object, additionalProperties: true, classFQCN}`; `classFQCN` comes from the `$classes` short-name map |
| unparseable | `{type: any}` |

New constants `TypeHintResolver::ITEMS = 'items'` and `ONE_OFF = 'oneOf'`.

`TypeHintResolver::getUsesNamespaces(string $classFQCN): array` returns the class's `use` aliases plus every `*.php` file in the class's directory, as short name → FQCN; `[]` for a non-existent class.

### JSON Schema → PHPDoc type (1.1.1)

`TypeHintResolver::jsonSchemaToTypeDescription(array $schema): string`: the six-type `oneOf` → `mixed`, `oneOf` of T and null → `?T`, other `oneOf` → `A|B`, `items` → `T[]` (or `array<T>` for a complex T), object with array `additionalProperties` → `array<string,T>`, object with `classFQCN` → that class, no `type` → `mixed`.

`TypeHintResolver::checkMixedInSchema(array $schema): bool` — true when `oneOf` is exactly the six basic types.

```php
TypeHintResolver::typeDescriptionToJsonSchema('\DummyDTO[]', ['DummyDTO' => DummyDTO::class]);
// ['type' => 'array', 'items' => ['type' => 'object', 'additionalProperties' => true, 'classFQCN' => DummyDTO::class]]

TypeHintResolver::jsonSchemaToTypeDescription(['oneOf' => [['type' => 'string'], ['type' => 'null']]]); // '?string'
```

## Upgrade notes

1. Two new runtime dependencies: `phpdocumentor/type-resolver` and `phpdocumentor/reflection-docblock`. No public signatures changed.
