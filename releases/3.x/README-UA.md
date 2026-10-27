[English](README.md)

# ufo-tech/dto-transformer 3.x

Мажорна версія 3 перебудувала трансформер у замінні сервіси за статичним фасадом `DTOTransformer` / `ServiceTransformer`, який тепер треба ініціалізувати. Протягом лінійки 3.x пакет отримав:
- ланцюги гідраторів і нормалізаторів, побудову схеми типів, метадані з reflection з runtime LRU-кешем і необов'язковим persistent-кешем
- `TransformerBootstrapper` (3.0.4)
- гідратацію й експорт DateTime з контекстом формату й часових поясів
- `#[StrictMode]` / `@strictMode`, `#[SerializationContext]`, `AttrDTO::C_STRICT`, типізовані винятки, `$context` усюди
- підтримку Symfony 8 (3.1)

## Вимоги

| | 3.0.0 | 3.1.0 |
|---|---|---|
| PHP | `>=8.3` | `>=8.4` |
| `symfony/serializer`, `symfony/validator` | `^7.2` | `^7.2\|^8.0` |
| `symfony/cache-contracts` | `^3.5` | `^3.5` |
| `phpdocumentor/type-resolver` | `^1.10` | `^1.10` |
| `phpdocumentor/reflection-docblock` | `^5.6` | `^5.6` |
| Інше | `ext-intl` | `ext-intl` |

## Версії

| Версія | Головне |
| --- | --- |
| [3.1](3.1/README-UA.md) | Symfony 8, PHP 8.4 |
| [3.0](3.0/README-UA.md) | Сервісна архітектура, кешування, `boot()`, DateTime, strict mode; 3.0.4: `TransformerBootstrapper` |

## Оновлення з 2.x

Зміни, що ламають сумісність, у 3.0:

- **Ініціалізуйте статичний API**: `TransformerBootstrapper::boot()` (3.0.4+) або `DTOTransformer::boot(DefaultDTOTransformerFactory::default()->create())` і те саме для `ServiceTransformer`; без цього статичні методи й DTO-трейти кидають `NotInitializeException`.
- **Прибрано з `DTOTransformer`**: `fromSmartArray()`, `transformEnum()` і protected static хелпери; `Interfaces\IDTOFromSmartArrayTransformer` більше немає.
- **Сигнатури**: `BaseDTOFromArrayTransformer::transformFromArray()` — `abstract public static` з `array $context = []`; перевизначення `fromArray()` / `toArray()` додають `array $context = []`.
- **Поведінка**: DateTime експортується рядком, невідоме значення enum кидає виняток, вкладені `toArray()` не викликаються, nullable-типи пишуться як `null|T`.
- Нова залежність `symfony/cache-contracts ^3.5`.

Деталі — у [3.0](3.0/README-UA.md#примітки-до-оновлення). 3.1 потребує PHP 8.4 ([3.1](3.1/README-UA.md#примітки-до-оновлення)).

Відомі проблеми всіх 3.x: `ServiceTransformer::toArray()` повертає лише `['$classFQCN' => …]`, `toArray()` експортує властивості з `#[Ignore]`. **Виправлено в [4.0](../4.x/4.0/README-UA.md).**
