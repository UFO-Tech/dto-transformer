[Українська](README-UA.md)

# ufo-tech/dto-transformer 4.x

Major 4 moved the classes to new namespaces (21 of the 3.x names stay as deprecated aliases until 4.1), built hydration on cached class schemas, made the validator a dependency of the factory and changed the signatures of `DefaultDTOTransformerFactory::default()` and of the transformer constructors.

## Requirements

| | 4.0.0 |
|---|---|
| PHP | `>=8.4` |
| `symfony/serializer`, `symfony/validator` | `^7.2\|^8.0` |
| `symfony/cache-contracts` | `^3.5` |
| `phpdocumentor/type-resolver` | `^1.10` |
| `phpdocumentor/reflection-docblock` | `^5.6` |
| Other | `ext-intl` |

## Versions

| Version | Highlights |
| --- | --- |
| [4.0](4.0/README.md) | New namespaces with 21 deprecated aliases, class schemas and bounded caches, injected validator, `default()` signature; fixes for `#[Ignore]` in `toArray()`, `ServiceTransformer::toArray()`, `renameKey`, `#[AttrAssertions]`, `null` in `#[AttrDTO]` and nullable dates |

## Upgrading from 3.x

Breaking changes in 4.0:

- **Names**: classes moved to `Contract`, `Factory`, `Schema`, `Transformer`; only 21 old names stay as aliases, and they are removed in 4.1 — move to the 4.0 names.
- **`DefaultDTOTransformerFactory::default()`**: `bool $recreate` is gone; the second argument is `int $runtimeCacheLimit`, the third `?ValidatorInterface $validator`.
- **Manual wiring**: constructors of the factories, `DTOFromArrayTransformer`, `DTONormalizer` and the metadata classes changed; `DTOAttributesEnum` is replaced by `AssertionsValidator` and `AttrDTOResolver`; custom `TypeSchemaResolverInterface`, `ReflectionMetadataProviderInterface` and `SerializationContextAttributeReaderInterface` implementations need new methods.
- **Behaviour**: `toArray()` skips `#[Ignore]`, `?DateTime` with `null` stays `null`, `#[AttrAssertions]` skips missing keys, a custom `AttrDTO::C_TRANSFORMER` must implement `StaticDTOFromArrayTransformerInterface`.
- Clear the persistent cache on deploy.

Full list in [4.0](4.0/README.md#upgrade-notes).
