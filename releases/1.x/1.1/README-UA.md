[English](README.md)

# 1.1 — типи PHPDoc ⇄ JSON Schema

Теги: 1.1.0, 1.1.1. Перший реліз: 2025-07-09.

## Головне

- `TypeHintResolver::typeDescriptionToJsonSchema()` перетворює вираз типу PHPDoc на JSON Schema; `jsonSchemaToTypeDescription()` — навпаки (1.1.1).
- `TypeHintResolver::getUsesNamespaces()` зіставляє короткі імена класів з FQCN.
- Нові залежності: `phpdocumentor/type-resolver ^1.10`, `phpdocumentor/reflection-docblock ^5.6`.

## Що нового

### Тип PHPDoc → JSON Schema (1.1.0)

`TypeHintResolver::typeDescriptionToJsonSchema(string $typeExpression, array $classes = []): array`:

| PHPDoc | JSON Schema |
|---|---|
| скаляр | `{type}` |
| `T[]`, `array<int, T>` | `{type: array, items: <T>}` |
| `array<string, T>` | `{type: object, additionalProperties: <T>}` |
| `A\|B`, `?T` | `oneOf` |
| `mixed` | `oneOf` з шести базових типів |
| клас | `{type: object, additionalProperties: true, classFQCN}`; `classFQCN` береться з мапи коротких імен `$classes` |
| не розбирається | `{type: any}` |

Нові константи `TypeHintResolver::ITEMS = 'items'` і `ONE_OFF = 'oneOf'`.

`TypeHintResolver::getUsesNamespaces(string $classFQCN): array` повертає `use`-аліаси класу плюс усі `*.php` з його теки у вигляді коротке ім'я → FQCN; `[]` для неіснуючого класу.

### JSON Schema → тип PHPDoc (1.1.1)

`TypeHintResolver::jsonSchemaToTypeDescription(array $schema): string`: `oneOf` з шести типів → `mixed`, `oneOf` з T і null → `?T`, інший `oneOf` → `A|B`, `items` → `T[]` (або `array<T>` для складного T), об'єкт з масивом `additionalProperties` → `array<string,T>`, об'єкт з `classFQCN` → цей клас, без `type` → `mixed`.

`TypeHintResolver::checkMixedInSchema(array $schema): bool` — true, коли `oneOf` складається рівно з шести базових типів.

```php
TypeHintResolver::typeDescriptionToJsonSchema('\DummyDTO[]', ['DummyDTO' => DummyDTO::class]);
// ['type' => 'array', 'items' => ['type' => 'object', 'additionalProperties' => true, 'classFQCN' => DummyDTO::class]]

TypeHintResolver::jsonSchemaToTypeDescription(['oneOf' => [['type' => 'string'], ['type' => 'null']]]); // '?string'
```

## Примітки до оновлення

1. Дві нові runtime-залежності: `phpdocumentor/type-resolver` і `phpdocumentor/reflection-docblock`. Публічні сигнатури не змінились.
