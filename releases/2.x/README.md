[Українська](README-UA.md)

# ufo-tech/dto-transformer 2.x

Major 2 changed the `#[AttrDTO]` constructor to a `context` array and the signatures of custom transformers. Over 2.x the package added:
- smart arrays with `'$className'`, `fromSmartArray()` and `SmartArrayConvertibleTrait`; classes resolved from unions and short names through namespaces
- DocBlock types (`@var` / `@param` such as `array<A|B|Enum>`) for collections of DTOs and enums
- pure (`Unit`) enums in hydration, export and JSON Schema
- `JsonSerializableTrait`
- public-only export by default, hydration without the constructor, `mixed` properties (2.1)

## Requirements

| | 2.0.0 | 2.1.1 |
|---|---|---|
| PHP | `>=8.3` | `>=8.3` |
| `symfony/serializer`, `symfony/validator` | `^7.2` | `^7.2` |
| `phpdocumentor/type-resolver` | `^1.10` | `^1.10` |
| `phpdocumentor/reflection-docblock` | `^5.6` | `^5.6` |
| Other | `ext-intl` | `ext-intl` |

## Versions

| Version | Highlights |
| --- | --- |
| [2.1](2.1/README.md) | `toArray()` exports public properties only, hydration without the constructor, FQCN in `'$className'`, `mixed` |
| [2.0](2.0/README.md) | Smart arrays, DocBlock types, `AttrDTO` with `context`, pure enums, `JsonSerializableTrait` |

## Upgrading from 1.x

Breaking changes in 2.0:

- **`#[AttrDTO]`**: `collection`, `renameKeys` and `transformerFQCN` moved into `context: [AttrDTO::C_COLLECTION => …, …]`; the public properties are gone.
- **Custom transformers**: `IDTOFromArrayTransformer::fromArray()` and `BaseDTOFromArrayTransformer::transformFromArray()` take `array $namespaces = []`; subclasses of `BaseDTOFromArrayTransformer` implement `fromSmartArray()`.
- **Behaviour**: a public constructor is always called; enums are exported as their value or name; `NotSupportDTOException` extends `BadParamException`.

Use 2.0.3+ for correct `#[AttrAssertions]` and 2.0.5+ for `toArray()` without `'$className'` ([2.0](2.0/README.md#upgrade-notes)). 2.1 breaks again despite the minor version: new `toArray(bool $publicOnly)` signatures, private properties dropped from the export, constructors not called in `fromArray()` ([2.1](2.1/README.md#upgrade-notes)).
