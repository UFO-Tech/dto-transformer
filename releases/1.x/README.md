[Українська](README-UA.md)

# ufo-tech/dto-transformer 1.x

The first line of the package. Over 1.x it provided:
- two-way DTO ⇄ array conversion through the constructor and properties, with readonly support, key renaming, `#[Ignore]`, nested DTOs and the `IArrayConvertible` / `IArrayConstructible` traits
- `#[AttrDTO]` for nested DTOs, collections and custom transformers, `#[AttrAssertions]` for Symfony Validator constraints (enforced from 1.2.0)
- `ServiceTransformer` with `'$classFQCN'` for polymorphic round-trips
- union types resolved by the keys of the array (1.2) and backed enums (1.3)
- PHP type ⇄ JSON Schema helpers in `TypeHintResolver`, with `$ref` and namespace maps (1.1–1.3)
- enum JSON Schema with `x-ufo-enum`: `EnumResolver`, `EnumVO`, `StringTransformerEnum` (1.4)

## Requirements

| | 1.0.0 | 1.4.0 |
|---|---|---|
| PHP | `>=8.3` | `>=8.3` |
| `symfony/serializer` | `7.2.*` | `^7.2` (since 1.0.2) |
| `symfony/validator` | `^7.2` | `^7.2` |
| `phpdocumentor/type-resolver` | — | `^1.10` (since 1.1.0) |
| `phpdocumentor/reflection-docblock` | — | `^5.6` (since 1.1.0) |
| `ext-intl` | — | `*` (since 1.4.0) |

## Versions

| Version | Highlights |
| --- | --- |
| [1.4](1.4/README.md) | `EnumResolver` replaces `EnumsHelper` / `XUfoValuesEnum`; `EnumVO`, `StringTransformerEnum`; `ext-intl` |
| [1.3](1.3/README.md) | Backed enums in `fromArray()`, `$ref` with a namespace map, enum JSON Schema |
| [1.2](1.2/README.md) | Union types, `#[AttrAssertions]` enforced |
| [1.1](1.1/README.md) | PHPDoc type ⇄ JSON Schema, `getUsesNamespaces()` |
| [1.0](1.0/README.md) | `DTOTransformer`, `#[AttrDTO]`, `#[AttrAssertions]`, `ServiceTransformer` |

## Upgrade notes

Breaking changes inside 1.x:

- **1.0.1**: `TypeHintResolver::phpToJsonSchema()` accepts only a `string`; **1.0.3**: `isRealClass()` is `false` for enums, use `isEnum()` ([1.0](1.0/README.md#upgrade-notes)).
- **1.2.0**: `#[AttrAssertions]` failures and arrays that fit no class-typed property throw `BadParamException`; use 1.2.2+ for plain `array` properties and 1.2.3+ for members with several attributes ([1.2](1.2/README.md#upgrade-notes)).
- **1.3**: skip 1.3.0; the enum schema format changed in 1.3.5; `?string $namespace` became `array $namespaces` in 1.3.7 ([1.3](1.3/README.md#upgrade-notes)).
- **1.4.0**: `EnumsHelper` / `XUfoValuesEnum` are replaced by `EnumResolver`; `ext-intl` is required ([1.4](1.4/README.md#upgrade-notes)).
