[English](README.md)

# 2.0 — smart arrays, типи з DocBlock, `AttrDTO` з `context`, чисті enum-и

Теги: 2.0.0, 2.0.1, 2.0.2, 2.0.3, 2.0.4, 2.0.5, 2.0.6. Перший реліз: 2025-10-03.

## Головне

- **Smart arrays**: експортований об'єкт може нести `'$className'`, а `fromSmartArray()` збирає з нього об'єкт назад.
- **Типи з DocBlock**: `@var` / `@param` на кшталт `array<UserDto|DummyDTO|IntEnum>` будують колекції DTO та enum без атрибутів.
- **`#[AttrDTO]` приймає масив `context`** замість іменованих прапорців.
- **Чисті (`Unit`) enum-и** в гідратації, експорті та JSON Schema; enum-и експортуються скалярами.
- **`JsonSerializableTrait`** і `SmartArrayConvertibleTrait` (2.0.5).

## Що нового

### Smart arrays (2.0.0)

- Новий інтерфейс `Interfaces\IDTOFromSmartArrayTransformer::fromSmartArray(array $data, array $renameKey = [], array $namespaces = []): object`, його реалізує `DTOTransformer`.
- Клас читається з `$data['$className']`; простір імен — з `$namespaces[<className>]` або `$namespaces['$defaultNamespace']`. Константи `BaseDTOFromArrayTransformer::DTO_CLASSNAME = '$className'` і `DTO_NS_KEY = '$defaultNamespace'`.
- `fromArray()` спершу пробує smart array; вкладені масиви теж пробуються як smart arrays.
- `fromArray()` приймає union класів `'A|B|C'`: повертає перший клас, що підійшов, або кидає `BadParamException` з помилкою кожного класу. Короткі імена розв'язуються через `$namespaces`.

### Типи з DocBlock (2.0.0)

Читаються `@var` властивостей і `@param` конструктора. Для члена з типом `array` анотація (наприклад `array<UserDto|DummyDTO|IntEnum>`) автоматично створює `AttrDTO`, тож елементи колекції стають відповідним DTO або enum; елемент, що не перетворився, лишається як є. `@param` конструктора працює з 2.0.1, `array<string, Dto>` — з 2.0.2.

### `#[AttrDTO]` з `context` (2.0.0)

`AttrDTO(string $dtoFQCN, array $context = [])` з ключами `C_COLLECTION`, `C_RENAME_KEYS`, `C_TRANSFORMER`, `C_NS`, `C_IS_ENUM`, `C_PROPERTY` і методами `isCollection()`, `renameKeys()`, `getRenameKey()`, `transformerFQCN()`, `namespaces()`, `isEnum()`, `getContextByKey()`.

```php
// 1.4: #[AttrDTO(User::class, collection: true, renameKeys: ['randomNumber' => null])]
#[AttrDTO(User::class, context: [
    AttrDTO::C_COLLECTION => true,
    AttrDTO::C_RENAME_KEYS => ['randomNumber' => null],
])]
```

### Enum-и (2.0.0)

- Новий `DTOTransformer::transformEnum(string $enumFQCN, string|int $value): UnitEnum|string|int`: backed enum використовує `tryFrom` (інакше `BadParamException('Invalid value "%s" for enum %s')`), чистий enum шукає ім'я кейсу без урахування регістру і повертає сире значення, якщо нічого не знайшов.
- `toArray()` експортує `BackedEnum` як `->value`, чистий enum — як `->name`.
- `EnumResolver`: нові `enumsDataFromSchema()`, `applyEnumFqcnToJsonSchema()`, `getEnumFQCN()`; `enumDataFromSchema()` отримав `int $resultKey = 0` і кидає `NotSupportDTOException`, коли enum не знайдено; `schemaHasEnum()` обходить вкладені `oneOf` / `items`.
- `TypeHintResolver`: нові `typeWithNamespaceOrDefault()`, `applyToSchema()`, `filterSchema()`.
- `EnumVO::fromSchema()` зіставляє JSON `integer` з `EnumResolver::INT` (було `STRING`).

### Трейти

- **2.0.0** `JsonSerializableTrait`: `jsonSerialize(): string` повертає `json_encode($this->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)` — рядок JSON, а не масив. Його використовує `ArrayConvertibleTrait`.
- **2.0.5** `SmartArrayConvertibleTrait`: `toArray()` експортує з `'$className'`.

```php
class UserDto
{
    use SmartArrayConvertibleTrait;
}

$dto->toArray(); // містить '$className'
```

## Зміни та виправлення

- **2.0.0**: під час гідратації завжди викликається публічний конструктор (в 1.4 — лише для класів з readonly-властивостями). `DTOTransformer::isSupportClass()` повертає `class_exists()`. `NotSupportDTOException` наслідує `BadParamException`. Короткі імена класів розв'язуються через простір імен DTO замість сканування його теки. `StringTransformerEnum::transformName()` лишає рядок у верхньому регістрі як є.
- **2.0.1**: аргументи конструктора передаються за іменем — виправляє порядок аргументів, коли параметр пропущено через `#[Ignore]` або перейменування в `null`. Типи з `@param` конструктора застосовуються (у 2.0.0 мовчки ігнорувались).
- **2.0.2**: `TypeHintResolver::applyToSchema()` / `filterSchema()` обходять `additionalProperties`, тож `array<string, Dto>` розв'язує DTO або enum елемента. Нові константи `TypeHintResolver::ADDITIONAL_PROPERTIES`, `CLASS_FQCN`.
- **2.0.3**: `#[AttrAssertions]` перевіряє значення поля (у 2.0.0–2.0.2 — увесь вхідний масив). Відсутній ключ перевіряється як `[]`.
- **2.0.4**: члени union-а з JSON Schema дедуплікуються.
- **2.0.5**: `toArray()` за замовчуванням більше не додає `'$className'` (`asSmartArray` за замовчуванням `true` → `false`); беріть `SmartArrayConvertibleTrait` або `asSmartArray: true`. Вивід `ServiceTransformer::toArray()` втрачає `'$className'` і зберігає `'$classFQCN'`.
- **2.0.6**: `EnumResolver::generateEnumSchema()` підтримує чисті enum-и (`type: string`, `name => name`); раніше на них падав.

## Примітки до оновлення

1. **Перепишіть `#[AttrDTO(...)]`** на `context: [...]`; публічних властивостей `collection`, `renameKeys`, `transformerFQCN` більше немає.
2. **Власні трансформери**: `IDTOFromArrayTransformer::fromArray()` і `BaseDTOFromArrayTransformer::transformFromArray()` отримали `array $namespaces = []`; нащадки `BaseDTOFromArrayTransformer` мають реалізувати `fromSmartArray()`. Перевизначення protected-методів `DTOTransformer` (`extractValue`, `checkAttributes`, `convertValue`, `checkEnum`) мають відповідати новим сигнатурам.
3. **Enum-и експортуються значенням або ім'ям.**
4. **`catch (BadParamException)` ловить і `NotSupportDTOException`.**
5. **Конструктори завжди викликаються** під час гідратації.
6. Беріть 2.0.3+ для правильного `#[AttrAssertions]` і 2.0.5+, щоб `toArray()` був без `'$className'`.
