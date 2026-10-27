[English](README.md)

# 1.4 — `EnumResolver`, `EnumVO`, `StringTransformerEnum`

Теги: 1.4.0. Перший реліз: 2025-09-18.

## Головне

- `Helpers\EnumResolver` замінює `Helpers\EnumsHelper` і `Helpers\XUfoValuesEnum`.
- `VO\EnumVO` — опис enum, прочитаний з JSON Schema.
- `StringTransformerEnum` перетворює довільні рядки на імена кейсів enum. Тепер потрібен `ext-intl`.

## Що нового

### `EnumResolver`

`Ufo\DTO\Helpers\EnumResolver` (string-backed enum, кейси `STRING`, `INT`; константи `ENUM = 'x-ufo-enum'`, `ASSERTIONS`, `ENUM_NAME = 'name'`, `ENUM_KEY = 'enum'`, `METHOD_*`):

- `generateEnumSchema(string $enumFQCN, string $methodGetValues = 'values', string $methodTryFrom = 'tryFromValue'): array` — той самий вивід, що з 1.3.5. Тепер викликає саме передані методи (раніше завжди `values()` і `tryFromValue()`) і кидає `InvalidArgumentException`, коли try-метод повертає `null`.
- `findEnumNameInJsonSchema(array $type): ?string`
- `schemaHasEnum(array $schema): bool` — перевіряє корінь, `items` і `oneOf`.
- `enumDataFromSchema(array $schema, ?string $paramName = null): EnumVO`

```php
EnumResolver::generateEnumSchema(StringEnum::class);
// ['type' => 'string', 'x-ufo-enum' => ['name' => 'StringEnum', 'values' => ['A' => 'a', 'B' => 'b', 'C' => 'c']], 'enum' => ['a', 'b', 'c']]

$vo = EnumResolver::enumDataFromSchema($schema, 'status'); // $vo->name, $vo->type, $vo->values
```

### `EnumVO` і `StringTransformerEnum`

- `Ufo\DTO\VO\EnumVO` (readonly: `name`, `EnumResolver $type`, `array $values`). `fromSchema()` бере ім'я з `x-ufo-enum.name` або `ucfirst($paramName) . 'Enum'`. Тип — `EnumResolver::tryFrom(<type зі схеми>) ?? STRING`, тож JSON `integer` стає `STRING` (правильно зіставляється з 2.0.0).
- `Ufo\DTO\StringTransformerEnum`: `transformName()` транслітерує, додає `_` перед внутрішніми великими літерами, переводить у верхній регістр і додає `case_` перед початковою цифрою; `alias()` перетворює `''` і `'--'` на `EMPTY`; `transliterate()` використовує `Transliterator` (`ext-intl`).

## Зміни та виправлення

- `TypeHintResolver::jsonSchemaToPhp()` розпізнає схему enum лише за `x-ufo-enum.name` (раніше потрібні були і `enum`, і `x-ufo-enum`).

## Примітки до оновлення

1. `EnumsHelper::generateEnumSchema()` → `EnumResolver::generateEnumSchema()`; другий параметр змінив значення.
2. `XUfoValuesEnum::*` → константи `EnumResolver::*` (рядки, а не кейси enum; `X_METHOD` прибрано).
3. Потрібен `ext-intl`.
