[English](README.md)

# 1.0 — DTO ⇄ масив, атрибути, `ServiceTransformer`

Теги: 1.0.0, 1.0.1, 1.0.2, 1.0.3. Перший реліз: 2025-05-19.

## Головне

- **`DTOTransformer`**: двобічне перетворення між масивами й DTO через публічний конструктор і властивості, з підтримкою readonly, перейменуванням ключів і Symfony `#[Ignore]`.
- **Атрибути**: `#[AttrDTO]` для вкладених DTO, колекцій і власних трансформерів; `#[AttrAssertions]` для обмежень Symfony Validator.
- **`ServiceTransformer`**: записує й читає `'$classFQCN'` для поліморфного round-trip.
- **`TypeHintResolver`**: перетворення типів PHP ⇄ JSON Schema.

## Що нового

### `DTOTransformer` (1.0.0)

`Ufo\DTO\DTOTransformer` наслідує `BaseDTOFromArrayTransformer` і реалізує `IDTOFromArrayTransformer` та `IDTOToArrayTransformer`:

- `fromArray(string $classFQCN, array $data, array $renameKey = []): object`
- `toArray(object $dto, array $renameKey = []): array`
- `isSupportClass(string $classFQCN): bool` — завжди `true`.

Клас із readonly-властивостями створюється через конструктор (`newInstanceArgs`), будь-який інший — без конструктора, після чого заповнюються властивості. Ключ вважається наявним за `array_key_exists`, тож явний `null` береться як значення. Властивості з типом-класом гідруються з вкладених масивів. `$renameKey` зіставляє ім'я в DTO з ключем у даних; `null` пропускає поле. У `toArray` вкладений об'єкт, що реалізує `IArrayConvertible`, експортується його власним `toArray()`, будь-який інший — рекурсивно.

`BaseDTOFromArrayTransformer::fromArray()` кидає `NotSupportDTOException`, коли `isSupportClass()` повертає `false`, і загортає будь-який `Throwable` з `transformFromArray()` у `BadParamException`.

Трейти для DTO: `ArrayConstructibleTrait::fromArray(array $data, array $renameKey = []): static` і `ArrayConvertibleTrait::toArray()`, з інтерфейсами `Interfaces\IArrayConstructible` та `Interfaces\IArrayConvertible`.

### Атрибути (1.0.0)

- `Attributes\AttrDTO(string $dtoFQCN, bool $collection = false, array $renameKeys = [], ?string $transformerFQCN = null)` — вкладений DTO або колекція DTO, за потреби зібрана іншим трансформером.
- `Attributes\AttrAssertions(array $assertions)` — Symfony `Constraint[]` для значення.

Обидва застосовуються до властивостей, параметрів і методів; нащадки атрибутів теж розпізнаються. Їх обробляє `DTOAttributesEnum` (кейси `ASSERTIONS`, `DTO`).

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

`Ufo\DTO\ServiceTransformer extends DTOTransformer`: `toArray()` додає `'$classFQCN' => $dto::class`, а `fromArray()` бере `$data['$classFQCN']` як цільовий клас, якщо він є.

### Хелпери (1.0.0)

- `Helpers\Validator` — `validate()`, `getData()`, `getErrors()`, `hasErrors()`, `getCurrentError()`, `throw()`.
- `Helpers\TypeHintResolver` — аліаси типів (string-backed enum) з `normalize()`, `normalizeArray()`, `isRealClass()`, `jsonSchemaToPhp()` (з `oneOf`), `phpToJsonSchema()`, `mixedForJsonSchema()`.
- Винятки `Exceptions\BadParamException` і `Exceptions\NotSupportDTOException`, обидва наслідують `\InvalidArgumentException`.

## Зміни та виправлення

- **1.0.1**: новий кейс `TypeHintResolver::COLLECTION = 'collection'`; `normalize('collection')` повертає `'array'` (було `'object'`). `phpToJsonSchema()` приймає лише `string` (було `array|string`).
- **1.0.2**: обмеження `symfony/serializer` `7.2.*` → `^7.2`.
- **1.0.3**: `TypeHintResolver::isRealClass()` повертає `false` для enum-ів; новий `TypeHintResolver::isEnum(string $value): bool`.

У 1.0 невдала перевірка `#[AttrAssertions]` всередині `DTOTransformer::fromArray()` ковтається: будь-яка помилка атрибута ігнорується. Помилки валідації кидаються з 1.2.0.

## Примітки до оновлення

1. **1.0.1**: передача масиву в `phpToJsonSchema()` тепер дає `TypeError`.
2. **1.0.3**: код, що покладається на `isRealClass(SomeEnum::class) === true`, має перейти на `isEnum()`.
