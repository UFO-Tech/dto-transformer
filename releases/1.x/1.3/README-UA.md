[English](README.md)

# 1.3 — backed enum-и, `$ref` і схеми enum

Теги: 1.3.0, 1.3.1, 1.3.2, 1.3.3, 1.3.4, 1.3.5, 1.3.6, 1.3.7. Перший реліз: 2025-08-21.

## Головне

- **Backed enum-и у `fromArray()`**: скаляр стає кейсом enum, коли член типізований backed enum-ом (1.3.0, перероблено в 1.3.1).
- **`$ref` у JSON Schema** з мапою просторів імен (1.3.2 – 1.3.7).
- **JSON Schema для enum** з розширенням `x-ufo-enum`: `Helpers\EnumsHelper` і `Helpers\XUfoValuesEnum` (1.3.4, формат змінено в 1.3.5).

## Що нового

### Backed enum-и (1.3.0, 1.3.1)

1.3.0 перетворював скаляр через `$enumFQCN::from($value)` для кожного наявного ключа. Він безумовно читав ім'я типу, тож члени з union-типом і без типу падали з `BadParamException`. 1.3.1 переніс перетворення в `DTOTransformer::checkEnum()`: воно виконується після атрибутів, лише для значень `string|int` і одного (або nullable) backed enum-типу, а при невдачі лишає сире значення.

### `$ref` і простори імен (1.3.2 – 1.3.7)

- **1.3.2**: `TypeHintResolver::jsonSchemaToPhp(array|string $type, ?string $namespace = null)` розуміє `$ref`: `'#/components/schemas/X'` → `$namespace . '\X'`. Нова константа `TypeHintResolver::REF = '$ref'`.
- **1.3.3**: члени `oneOf` розв'язуються рекурсивно як повні схеми; без простору імен `{type: object, additionalProperties}` → `'array'`.
- **1.3.6**: `$ref` без простору імен дає `'X'` (було `'\X'`); `jsonSchemaToTypeDescription()` передає простір імен у вкладені `oneOf`, `additionalProperties` і `items`.
- **1.3.7**: простір імен став мапою: `jsonSchemaToPhp(array|string $type, array $namespaces = [])` і `jsonSchemaToTypeDescription(array $schema, array $namespaces = [])`. Ключі — короткі імена типів; запасні ключі — `'dto'` для типів з `$ref` і `'enum'` для enum-ів. Схема з `enum` і `x-ufo-enum` перетворюється назад на ім'я enum.

```php
TypeHintResolver::jsonSchemaToPhp(['$ref' => '#/Qqq'], ['Qqq' => 'DTO']);                // 'DTO\Qqq'
TypeHintResolver::jsonSchemaToTypeDescription($stringEnumSchema, ['enum' => 'DefaultNS']); // 'DefaultNS\StringEnum'
```

### Схема enum (1.3.4, 1.3.5)

`Helpers\EnumsHelper::generateEnumSchema(string $enumFQCN, string $method = 'values'): array` і ключі `Helpers\XUfoValuesEnum` (`x-ufo`, `x-ufo-enum`, `x-ufo-assertions`, `x-method`, …). `typeDescriptionToJsonSchema(EnumClass)` повертає цю схему. Формат з 1.3.5:

```php
['type' => 'string', 'x-ufo-enum' => ['name' => 'StringEnum', 'values' => ['A' => 'a', 'B' => 'b']], 'enum' => ['a', 'b']]
```

У 1.3.4 ім'я лежало в окремому ключі `x-ufo-enum-name`, а `x-ufo-enum` містив лише значення.

## Зміни та виправлення

- **1.3.4**: `jsonSchemaToPhp()` перетворює `oneOf` з T і null на `?T`; об'єкт з `additionalProperties` без `classFQCN` — `'array'` незалежно від простору імен, тож `jsonSchemaToTypeDescription()` описує `object[]` як `array[]`. `jsonSchemaToTypeDescription()` отримав параметр простору імен і підтримує `$ref`. `checkMixedInSchema()` приймає члени без `type`.

## Примітки до оновлення

1. Пропустіть 1.3.0: члени з union-типом і без типу там падають. Беріть 1.3.1 або новішу.
2. Формат схеми enum відрізняється між 1.3.4 і 1.3.5+.
3. Схеми об'єктів без `classFQCN` описуються як `array` з 1.3.4.
4. `$ref` без простору імен втратив початковий `\` в 1.3.6.
5. У 1.3.7 параметр `?string $namespace` став `array $namespaces`.
