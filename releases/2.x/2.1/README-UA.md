[English](README.md)

# 2.1 — експорт лише публічних властивостей, гідратація без конструктора, `mixed`

Теги: 2.1.0, 2.1.1. Перший реліз: 2026-02-25.

## Головне

- **`toArray()` за замовчуванням експортує лише публічні властивості**; `publicOnly: false` повертає старий вивід.
- **Гідратація більше не викликає конструктор**: усі властивості, зокрема readonly, заповнюються через reflection.
- **`$className` у smart arrays — FQCN**; 2.1.1 читає і FQCN, і короткі імена.
- **Властивості `mixed`** приймають масиви (2.1.1).

Це зміни, що ламають сумісність, попри мінорну версію.

## Що нового

### `publicOnly` (2.1.0)

- `IArrayConvertible::toArray(bool $publicOnly = true): array` (було `toArray(): array`).
- `IDTOToArrayTransformer::toArray(object $dto, array $renameKey = [], bool $asSmartArray = false, bool $publicOnly = true): array`.
- Той самий параметр у `DTOTransformer::toArray()`, `ArrayConvertibleTrait`, `SmartArrayConvertibleTrait` і `ServiceTransformer::toArray()`. Він передається у вкладені об'єкти.
- `JsonSerializableTrait::isPublicOnly(): bool` (повертає `true`) — перевизначте, щоб змінити, що експортує `jsonSerialize()`.

```php
$dto->toArray();                    // лише публічні властивості
$dto->toArray(publicOnly: false);   // також private і protected, як до 2.1
```

## Зміни та виправлення

- **2.1.0**: `fromArray()` створює об'єкт через `newInstanceWithoutConstructor()` і заповнює всі властивості через reflection; код конструктора більше не виконується. Відсутній обов'язковий ключ дає `Missing required key for property: '<key>'` (було `... for constructor param: ...`). Protected `checkReadonlyInClass()` прибрано.
- **2.1.0**: smart arrays пишуть у `'$className'` FQCN (було коротке ім'я). Масив, перший елемент якого має `'$className'`, гідрується поелементно; елемент, що не вдався, лишається сирим.
- **2.1.0**: `ServiceTransformer::fromArray()` передає `$namespaces` далі; `ServiceTransformer::toArray()` передає `$asSmartArray` і `$publicOnly`, тож з його `asSmartArray = true` за замовчуванням вивід знову містить `'$className'` поруч із `'$classFQCN'`.
- **2.1.1**: член `mixed` приймає масив як є (раніше: `BadParamException("Cannot assign array to property ...")`).
- **2.1.1**: `fromSmartArray()` приймає FQCN у `'$className'` і повертається до розв'язання через простір імен, тож вивід 2.1.0 читається назад.

## Примітки до оновлення

1. **Оновіть сигнатури `toArray`** у класах, що реалізують `IArrayConvertible` або `IDTOToArrayTransformer`; інакше PHP дасть помилку несумісного оголошення.
2. **Private і protected властивості зникають з `toArray()`** — передайте `publicOnly: false`, якщо вони потрібні.
3. **Конструктори не викликаються у `fromArray()`.** Значення, які обчислювались у конструкторі, мають прийти у вхідних даних.
4. **`'$className'` у smart array — FQCN** — беріть 2.1.1 або новішу, щоб прочитати його назад.
