[English](README.md)

# ufo-tech/dto-transformer 2.x

Мажорна версія 2 змінила конструктор `#[AttrDTO]` на масив `context` і сигнатури власних трансформерів. Протягом лінійки 2.x пакет отримав:
- smart arrays з `'$className'`, `fromSmartArray()` і `SmartArrayConvertibleTrait`; класи з union-ів і коротких імен через простори імен
- типи з DocBlock (`@var` / `@param` на кшталт `array<A|B|Enum>`) для колекцій DTO та enum
- чисті (`Unit`) enum-и в гідратації, експорті та JSON Schema
- `JsonSerializableTrait`
- експорт лише публічних властивостей за замовчуванням, гідратацію без конструктора, властивості `mixed` (2.1)

## Вимоги

| | 2.0.0 | 2.1.1 |
|---|---|---|
| PHP | `>=8.3` | `>=8.3` |
| `symfony/serializer`, `symfony/validator` | `^7.2` | `^7.2` |
| `phpdocumentor/type-resolver` | `^1.10` | `^1.10` |
| `phpdocumentor/reflection-docblock` | `^5.6` | `^5.6` |
| Інше | `ext-intl` | `ext-intl` |

## Версії

| Версія | Головне |
| --- | --- |
| [2.1](2.1/README-UA.md) | `toArray()` експортує лише публічні властивості, гідратація без конструктора, FQCN у `'$className'`, `mixed` |
| [2.0](2.0/README-UA.md) | Smart arrays, типи з DocBlock, `AttrDTO` з `context`, чисті enum-и, `JsonSerializableTrait` |

## Оновлення з 1.x

Зміни, що ламають сумісність, у 2.0:

- **`#[AttrDTO]`**: `collection`, `renameKeys` і `transformerFQCN` перенесено в `context: [AttrDTO::C_COLLECTION => …, …]`; публічних властивостей більше немає.
- **Власні трансформери**: `IDTOFromArrayTransformer::fromArray()` і `BaseDTOFromArrayTransformer::transformFromArray()` приймають `array $namespaces = []`; нащадки `BaseDTOFromArrayTransformer` реалізують `fromSmartArray()`.
- **Поведінка**: публічний конструктор викликається завжди; enum-и експортуються значенням або ім'ям; `NotSupportDTOException` наслідує `BadParamException`.

Беріть 2.0.3+ для правильного `#[AttrAssertions]` і 2.0.5+, щоб `toArray()` був без `'$className'` ([2.0](2.0/README-UA.md#примітки-до-оновлення)). 2.1 знову ламає сумісність попри мінорну версію: нові сигнатури `toArray(bool $publicOnly)`, приватні властивості зникають з експорту, конструктори не викликаються у `fromArray()` ([2.1](2.1/README-UA.md#примітки-до-оновлення)).
