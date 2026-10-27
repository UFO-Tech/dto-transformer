[Українська](README-UA.md)

# ufo-tech/dto-transformer 3.x

Major 3 rebuilt the transformer as replaceable services behind the static `DTOTransformer` / `ServiceTransformer` facade, which now has to be booted. Over 3.x the package added:
- chains of hydrators and normalizers, a type schema resolver, reflection metadata with a runtime LRU cache and an optional persistent cache
- `TransformerBootstrapper` (3.0.4)
- DateTime hydration and export with format and timezone context
- `#[StrictMode]` / `@strictMode`, `#[SerializationContext]`, `AttrDTO::C_STRICT`, typed exceptions, `$context` throughout
- Symfony 8 support (3.1)

## Requirements

| | 3.0.0 | 3.1.0 |
|---|---|---|
| PHP | `>=8.3` | `>=8.4` |
| `symfony/serializer`, `symfony/validator` | `^7.2` | `^7.2\|^8.0` |
| `symfony/cache-contracts` | `^3.5` | `^3.5` |
| `phpdocumentor/type-resolver` | `^1.10` | `^1.10` |
| `phpdocumentor/reflection-docblock` | `^5.6` | `^5.6` |
| Other | `ext-intl` | `ext-intl` |

## Versions

| Version | Highlights |
| --- | --- |
| [3.1](3.1/README.md) | Symfony 8, PHP 8.4 |
| [3.0](3.0/README.md) | Service architecture, caching, `boot()`, DateTime, strict mode; 3.0.4: `TransformerBootstrapper` |

## Upgrading from 2.x

Breaking changes in 3.0:

- **Boot the static API**: `TransformerBootstrapper::boot()` (3.0.4+) or `DTOTransformer::boot(DefaultDTOTransformerFactory::default()->create())` and the same for `ServiceTransformer`; without it the static methods and the DTO traits throw `NotInitializeException`.
- **Removed from `DTOTransformer`**: `fromSmartArray()`, `transformEnum()` and the protected static helpers; `Interfaces\IDTOFromSmartArrayTransformer` is gone.
- **Signatures**: `BaseDTOFromArrayTransformer::transformFromArray()` is `abstract public static` with `array $context = []`; overrides of `fromArray()` / `toArray()` add `array $context = []`.
- **Behaviour**: DateTime is exported as a string, an unknown enum value throws, nested `toArray()` methods are not called, nullable types are written `null|T`.
- New dependency `symfony/cache-contracts ^3.5`.

Details in [3.0](3.0/README.md#upgrade-notes). 3.1 requires PHP 8.4 ([3.1](3.1/README.md#upgrade-notes)).

Known issues across 3.x: `ServiceTransformer::toArray()` returns only `['$classFQCN' => …]`, `toArray()` exports properties with `#[Ignore]`. **Fixed in [4.0](../4.x/4.0/README.md).**
