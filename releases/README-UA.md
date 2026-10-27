[English](README.md)

# Опис релізів

Що додано в кожній версії і як цим користуватися. Для кожної мінорної версії є окрема сторінка, для кожної мажорної — огляд із вимогами та примітками до оновлення: [4.x](4.x/README-UA.md) · [3.x](3.x/README-UA.md) · [2.x](2.x/README-UA.md) · [1.x](1.x/README-UA.md).

«Реліз» — дата першого тегу мінорної версії.

| Версія | Реліз | Головне |
| --- | --- | --- |
| [4.0](4.x/4.0/README-UA.md) | не випущено | Нові простори імен з 21 deprecated-аліасом, схеми класів і обмежені кеші, validator як залежність, сигнатура `default()`; виправлення `#[Ignore]` у `toArray()`, `ServiceTransformer::toArray()`, `renameKey`, `#[AttrAssertions]`, `null` в `#[AttrDTO]` і nullable-дат |
| [3.1](3.x/3.1/README-UA.md) | 2026-09-25 | Symfony 8, PHP 8.4 |
| [3.0](3.x/3.0/README-UA.md) | 2026-05-25 | Сервісна архітектура, кешування, `boot()`, DateTime, strict mode; 3.0.4: `TransformerBootstrapper` |
| [2.1](2.x/2.1/README-UA.md) | 2026-02-25 | `toArray()` експортує лише публічні властивості, гідратація без конструктора, FQCN у `'$className'`, `mixed` |
| [2.0](2.x/2.0/README-UA.md) | 2025-10-03 | Smart arrays, типи з DocBlock, `AttrDTO` з `context`, чисті enum-и, `JsonSerializableTrait` |
| [1.4](1.x/1.4/README-UA.md) | 2025-09-18 | `EnumResolver`, `EnumVO`, `StringTransformerEnum`; `ext-intl` |
| [1.3](1.x/1.3/README-UA.md) | 2025-08-21 | Backed enum-и у `fromArray()`, `$ref` з мапою просторів імен, JSON Schema для enum |
| [1.2](1.x/1.2/README-UA.md) | 2025-07-13 | Union-типи, `#[AttrAssertions]` справді перевіряється |
| [1.1](1.x/1.1/README-UA.md) | 2025-07-09 | Тип PHPDoc ⇄ JSON Schema |
| [1.0](1.x/1.0/README-UA.md) | 2025-05-19 | `DTOTransformer`, `#[AttrDTO]`, `#[AttrAssertions]`, `ServiceTransformer` |
