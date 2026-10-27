[Українська](README-UA.md)

# Release notes

What was added in every version and how to use it. Each minor version has its own page; each major has an overview with requirements and upgrade notes: [4.x](4.x/README.md) · [3.x](3.x/README.md) · [2.x](2.x/README.md) · [1.x](1.x/README.md).

"Released" is the date of the first tag of the minor version.

| Version | Released | Highlights |
| --- | --- | --- |
| [4.0](4.x/4.0/README.md) | not released | New namespaces with 21 deprecated aliases, class schemas and bounded caches, injected validator, `default()` signature; fixes for `#[Ignore]` in `toArray()`, `ServiceTransformer::toArray()`, `renameKey`, `#[AttrAssertions]`, `null` in `#[AttrDTO]` and nullable dates |
| [3.1](3.x/3.1/README.md) | 2026-09-25 | Symfony 8, PHP 8.4 |
| [3.0](3.x/3.0/README.md) | 2026-05-25 | Service architecture, caching, `boot()`, DateTime, strict mode; 3.0.4: `TransformerBootstrapper` |
| [2.1](2.x/2.1/README.md) | 2026-02-25 | `toArray()` exports public properties only, hydration without the constructor, FQCN in `'$className'`, `mixed` |
| [2.0](2.x/2.0/README.md) | 2025-10-03 | Smart arrays, DocBlock types, `AttrDTO` with `context`, pure enums, `JsonSerializableTrait` |
| [1.4](1.x/1.4/README.md) | 2025-09-18 | `EnumResolver`, `EnumVO`, `StringTransformerEnum`; `ext-intl` |
| [1.3](1.x/1.3/README.md) | 2025-08-21 | Backed enums in `fromArray()`, `$ref` with a namespace map, enum JSON Schema |
| [1.2](1.x/1.2/README.md) | 2025-07-13 | Union types, `#[AttrAssertions]` enforced |
| [1.1](1.x/1.1/README.md) | 2025-07-09 | PHPDoc type ⇄ JSON Schema |
| [1.0](1.x/1.0/README.md) | 2025-05-19 | `DTOTransformer`, `#[AttrDTO]`, `#[AttrAssertions]`, `ServiceTransformer` |
