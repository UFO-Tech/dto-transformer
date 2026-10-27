[English](README.md)

# ufo-tech/dto-transformer 4.x

Мажорна версія 4 перенесла класи в нові простори імен (21 ім'я 3.x лишається deprecated-аліасом до 4.1), побудувала гідратацію на кешованих схемах класів, зробила validator залежністю фабрики й змінила сигнатури `DefaultDTOTransformerFactory::default()` і конструкторів трансформерів.

## Вимоги

| | 4.0.0 |
|---|---|
| PHP | `>=8.4` |
| `symfony/serializer`, `symfony/validator` | `^7.2\|^8.0` |
| `symfony/cache-contracts` | `^3.5` |
| `phpdocumentor/type-resolver` | `^1.10` |
| `phpdocumentor/reflection-docblock` | `^5.6` |
| Інше | `ext-intl` |

## Версії

| Версія | Головне |
| --- | --- |
| [4.0](4.0/README-UA.md) | Нові простори імен з 21 deprecated-аліасом, схеми класів і обмежені кеші, validator як залежність, сигнатура `default()`; виправлення `#[Ignore]` у `toArray()`, `ServiceTransformer::toArray()`, `renameKey`, `#[AttrAssertions]`, `null` в `#[AttrDTO]` і nullable-дат |

## Оновлення з 3.x

Зміни, що ламають сумісність, у 4.0:

- **Імена**: класи перенесено в `Contract`, `Factory`, `Schema`, `Transformer`; аліасами лишається лише 21 старе ім'я, і в 4.1 їх прибирають — переходьте на імена 4.0.
- **`DefaultDTOTransformerFactory::default()`**: `bool $recreate` прибрано; другий аргумент — `int $runtimeCacheLimit`, третій — `?ValidatorInterface $validator`.
- **Ручне підключення**: змінились конструктори фабрик, `DTOFromArrayTransformer`, `DTONormalizer` і класів метаданих; `DTOAttributesEnum` замінено на `AssertionsValidator` і `AttrDTOResolver`; власні реалізації `TypeSchemaResolverInterface`, `ReflectionMetadataProviderInterface` і `SerializationContextAttributeReaderInterface` потребують нових методів.
- **Поведінка**: `toArray()` пропускає `#[Ignore]`, `?DateTime` з `null` лишається `null`, `#[AttrAssertions]` не спрацьовує на відсутніх ключах, власний `AttrDTO::C_TRANSFORMER` має реалізувати `StaticDTOFromArrayTransformerInterface`.
- Очищайте persistent-кеш при деплої.

Повний перелік — у [4.0](4.0/README-UA.md#примітки-до-оновлення).
