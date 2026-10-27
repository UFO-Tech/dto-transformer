[English](README.md)

# ufo-tech/dto-transformer 1.x

Перша лінійка пакета. Протягом 1.x він дав:
- двобічне перетворення DTO ⇄ масив через конструктор і властивості з підтримкою readonly, перейменуванням ключів, `#[Ignore]`, вкладеними DTO і трейтами `IArrayConvertible` / `IArrayConstructible`
- `#[AttrDTO]` для вкладених DTO, колекцій і власних трансформерів, `#[AttrAssertions]` для обмежень Symfony Validator (справді перевіряються з 1.2.0)
- `ServiceTransformer` з `'$classFQCN'` для поліморфного round-trip
- union-типи, що визначаються за ключами масиву (1.2), і backed enum-и (1.3)
- перетворення типів PHP ⇄ JSON Schema в `TypeHintResolver` з `$ref` і мапою просторів імен (1.1–1.3)
- JSON Schema для enum з `x-ufo-enum`: `EnumResolver`, `EnumVO`, `StringTransformerEnum` (1.4)

## Вимоги

| | 1.0.0 | 1.4.0 |
|---|---|---|
| PHP | `>=8.3` | `>=8.3` |
| `symfony/serializer` | `7.2.*` | `^7.2` (з 1.0.2) |
| `symfony/validator` | `^7.2` | `^7.2` |
| `phpdocumentor/type-resolver` | — | `^1.10` (з 1.1.0) |
| `phpdocumentor/reflection-docblock` | — | `^5.6` (з 1.1.0) |
| `ext-intl` | — | `*` (з 1.4.0) |

## Версії

| Версія | Головне |
| --- | --- |
| [1.4](1.4/README-UA.md) | `EnumResolver` замість `EnumsHelper` / `XUfoValuesEnum`; `EnumVO`, `StringTransformerEnum`; `ext-intl` |
| [1.3](1.3/README-UA.md) | Backed enum-и у `fromArray()`, `$ref` з мапою просторів імен, JSON Schema для enum |
| [1.2](1.2/README-UA.md) | Union-типи, `#[AttrAssertions]` справді перевіряється |
| [1.1](1.1/README-UA.md) | Тип PHPDoc ⇄ JSON Schema, `getUsesNamespaces()` |
| [1.0](1.0/README-UA.md) | `DTOTransformer`, `#[AttrDTO]`, `#[AttrAssertions]`, `ServiceTransformer` |

## Примітки до оновлення

Зміни, що ламають сумісність, усередині 1.x:

- **1.0.1**: `TypeHintResolver::phpToJsonSchema()` приймає лише `string`; **1.0.3**: `isRealClass()` — `false` для enum-ів, беріть `isEnum()` ([1.0](1.0/README-UA.md#примітки-до-оновлення)).
- **1.2.0**: порушення `#[AttrAssertions]` і масиви, що не підходять до властивості з типом-класом, кидають `BadParamException`; беріть 1.2.2+ для властивостей просто `array` і 1.2.3+ для членів з кількома атрибутами ([1.2](1.2/README-UA.md#примітки-до-оновлення)).
- **1.3**: пропустіть 1.3.0; формат схеми enum змінився в 1.3.5; `?string $namespace` став `array $namespaces` у 1.3.7 ([1.3](1.3/README-UA.md#примітки-до-оновлення)).
- **1.4.0**: `EnumsHelper` / `XUfoValuesEnum` замінено на `EnumResolver`; потрібен `ext-intl` ([1.4](1.4/README-UA.md#примітки-до-оновлення)).
