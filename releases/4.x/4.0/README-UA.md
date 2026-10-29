[English](README.md)

# 4.0 — нові простори імен, схеми класів, validator як залежність, виправлення гідратації

Ще не випущено.

## Головне

- **Нова структура просторів імен.** Інтерфейси покинули `Ufo\DTO\Interfaces`, value objects — `Ufo\DTO\VO`; кожен клас тепер лежить поруч із шаром, що його використовує: `Contract`, `Factory`, `Schema` (`Enum`, `Meta`, `Type`), `Transformer` (`Context`, `Converter`, `Hydrator`, `Normalizer`). Гідратори й нормалізатори отримали узгоджені назви (`ParamHydrator` → `HydratorChain`, `PropertyNormalizer` → `NormalizerChain`, `DtoHydrator` → `DTOHydrator`, …).
- **21 старе ім'я лишається deprecated-аліасом** до 4.1: контракти `Interfaces\I*`, `VO\EnumVO`, `Helpers\TypeHintResolver`, `Helpers\EnumResolver`, інтерфейси трансформерів і фабрик, точки розширення гідраторів і нормалізаторів (`ParamHydratorInterface`, `AbstractParamHydrator`, `PropertyNormalizerInterface`, …) та `VO\TransformationContext` / `VO\NormalizationContext`. Решта перенесених імен аліасу не має.
- **Схеми класів.** Гідратація читає схему класу (властивості, типи, стратегія значень за замовчуванням), яка будується один раз і зберігається в runtime- і persistent-кеші. Усі кеші в процесі обмежені.
- **Validator — залежність**: `DefaultDTOTransformerFactory::default(validator: …)` замість нового `Validation::createValidator()` на кожну перевірку `#[AttrAssertions]`.
- **Змінилась сигнатура `default()`**: `default(?CacheInterface $persistentCache = null, int $runtimeCacheLimit = 2048, ?ValidatorInterface $validator = null)` — `bool $recreate` прибрано.
- **Виправлення**: `ServiceTransformer::toArray()` повертає весь масив, `toArray()` враховує `#[Ignore]`, `renameKey` бере значення за замовчуванням, `#[AttrAssertions]` перевіряє лише наявні ключі, `#[AttrDTO]` приймає `null`, `?DateTimeInterface` зберігає `null`, працюють значення за замовчуванням конструктора з об'єктом і enum, `Validator::throw()` враховує аргумент.

## Що нового

### Структура просторів імен

| Простір імен | Що там лежить |
|---|---|
| `Ufo\DTO\Contract` | Інтерфейси, які реалізують ваші DTO і статичні трансформери |
| `Ufo\DTO\Factory` | Фабрики трансформерів і нормалізаторів з їхніми інтерфейсами |
| `Ufo\DTO\Schema\Enum` | `EnumDefinition`, `EnumResolver`, `StringTransformerEnum` |
| `Ufo\DTO\Schema\Meta` | Метадані з reflection і docblock, strict mode |
| `Ufo\DTO\Schema\Type` | `TypeHintResolver`, побудова схеми типів |
| `Ufo\DTO\Transformer\Context` | `TransformationContext`, `NormalizationContext`, `TransformKey` |
| `Ufo\DTO\Transformer\Converter` | Конвертери значень |
| `Ufo\DTO\Transformer\Hydrator` | Гідратори (масив → об'єкт) і їхні трейти |
| `Ufo\DTO\Transformer\Normalizer` | Нормалізатори (об'єкт → масив) |

У `Ufo\DTO\Helpers` лишилися тільки загальні утиліти: `Validator`, `BoundedArray` і новий `StringListPool`. Фасади в кореневому просторі імен (`DTOTransformer`, `ServiceTransformer`, `BaseDTOFromArrayTransformer`, …), `Attributes`, `Exceptions` і кореневі трейти зберегли свої імена.

### Перенесені й перейменовані класи

Усі імена відносно `Ufo\DTO\`. «Аліас» — ім'я 3.x у 4.0 й далі резолвиться в новий тип.

| Ім'я 3.x | Ім'я 4.0 | Аліас |
|---|---|---|
| `Interfaces\IArrayConstructible` | `Contract\ArrayConstructibleInterface` | так |
| `Interfaces\IArrayConvertible` | `Contract\ArrayConvertibleInterface` | так |
| `Interfaces\IDTOFromArrayTransformer` | `Contract\StaticDTOFromArrayTransformerInterface` | так |
| `Interfaces\IDTOToArrayTransformer` | `Contract\StaticDTOToArrayTransformerInterface` | так |
| `VO\EnumVO` | `Schema\Enum\EnumDefinition` | так |
| `Helpers\EnumResolver` | `Schema\Enum\EnumResolver` | так |
| `Helpers\TypeHintResolver` | `Schema\Type\TypeHintResolver` | так |
| `StringTransformerEnum` | `Schema\Enum\StringTransformerEnum` | ні |
| `DTOAttributesEnum` | замінено на `Transformer\Attribute\AssertionsValidator` і `Transformer\Attribute\AttrDTOResolver` | ні |
| `Interfaces\DTOFromArrayTransformerInterface` | `Transformer\DTOFromArrayTransformerInterface` | так |
| `Interfaces\DTOFromSmartArrayTransformerInterface` | `Transformer\DTOFromSmartArrayTransformerInterface` | ні |
| `Interfaces\DTOToArrayTransformerInterface` | `Transformer\DTOToArrayTransformerInterface` | так |
| `Interfaces\Converter\DateTimeValueConverterInterface` | `Transformer\Converter\DateTimeValueConverterInterface` | ні |
| `Interfaces\Factory\DTOTransformerFactoryInterface` | `Factory\DTOTransformerFactoryInterface` | так |
| `Interfaces\Factory\DTOTransformerFromArrayFactoryInterface` | `Factory\DTOTransformerFromArrayFactoryInterface` | так |
| `Interfaces\Factory\DTOTransformerToArrayFactoryInterface` | `Factory\DTOTransformerToArrayFactoryInterface` | так |
| `Interfaces\Factory\PropertyNormalizerFactoryInterface` | `Factory\NormalizerChainFactoryInterface` | ні |
| `Factory\DefaultPropertyNormalizerFactory` | `Factory\DefaultNormalizerChainFactory` | ні |
| `Interfaces\Hydrator\ParamHydratorInterface` | `Transformer\Hydrator\HydratorInterface` | так |
| `Interfaces\Hydrator\ParamHydratorChainInterface` | `Transformer\Hydrator\HydratorChainInterface` | так |
| `Transformer\Hydrator\ParamHydrator` | `Transformer\Hydrator\HydratorChain` | ні |
| `Transformer\Hydrator\AbstractParamHydrator` | `Transformer\Hydrator\AbstractHydrator` | так |
| `Transformer\Hydrator\DtoHydrator` | `Transformer\Hydrator\DTOHydrator` | ні |
| `Transformer\Hydrator\EnumParamHydrator` | `Transformer\Hydrator\EnumHydrator` | ні |
| `Transformer\Hydrator\ScalarParamHydrator` | `Transformer\Hydrator\ScalarHydrator` | ні |
| `Transformer\Hydrator\UnionParamHydrator` | `Transformer\Hydrator\UnionHydrator` | ні |
| `Transformer\Traits\PayloadGuardTrait` | `Transformer\Hydrator\PayloadGuardTrait` | ні |
| `Transformer\Traits\ValueExtractorTrait` | `Transformer\Hydrator\ValueExtractorTrait` | ні |
| `Interfaces\Normalizer\PropertyNormalizerInterface` | `Transformer\Normalizer\NormalizerInterface` | так |
| `Interfaces\Normalizer\PropertyNormalizerChainInterface` | `Transformer\Normalizer\NormalizerChainInterface` | так |
| `Interfaces\Normalizer\DTONormalizerInterface` | `Transformer\Normalizer\DTONormalizerInterface` | ні |
| `Interfaces\Normalizer\SerializationContextProviderInterface` | `Schema\Meta\SerializationContextAttributeReaderInterface` | ні |
| `Transformer\Normalizer\PropertyNormalizer` | `Transformer\Normalizer\NormalizerChain` | ні |
| `Transformer\Normalizer\DtoNormalizer` | `Transformer\Normalizer\DTONormalizer` | ні |
| `Transformer\Normalizer\ScalarValueConverter` | `Transformer\Normalizer\ScalarNormalizer` | ні |
| `Transformer\Metadata\AttributeSerializationContextProvider` | `Schema\Meta\SerializationContextAttributeReader` | ні |
| `VO\TransformationContext` | `Transformer\Context\TransformationContext` | так |
| `VO\NormalizationContext` | `Transformer\Context\NormalizationContext` | так |
| `VO\TransformKeyVO` | `Transformer\Context\TransformKey` | ні |
| `Interfaces\Meta\DocBlockParserInterface` | `Schema\Meta\DocBlockParserInterface` | ні |
| `Interfaces\Meta\ReflectionMetadataProviderInterface` | `Schema\Meta\ReflectionMetadataProviderInterface` | ні |
| `Interfaces\Meta\StrictModeResolverInterface` | `Schema\Meta\StrictModeResolverInterface` | ні |
| `Interfaces\Meta\TypeSchemaResolverInterface` | `Schema\Type\TypeSchemaResolverInterface` | ні |
| `Transformer\Metadata\DocBlockMetadata` | `Schema\Meta\DocBlockMetadata` | ні |
| `Transformer\Metadata\PhpDocumentorDocBlockParser` | `Schema\Meta\PhpDocumentorDocBlockParser` | ні |
| `Transformer\Metadata\ReflectionMetadataKeyGenerator` | `Schema\Meta\ReflectionMetadataKeyGenerator` | ні |
| `Transformer\Metadata\ReflectionMetadataProvider` | `Schema\Meta\ReflectionMetadataProvider` | ні |
| `Transformer\Metadata\RuntimeReflectionCache` | `Schema\Meta\RuntimeReflectionCache` | ні |
| `Transformer\Metadata\StrictModeResolver` | `Schema\Meta\StrictModeResolver` | ні |
| `Transformer\Type\ReflectionTypeSchemaResolver` | `Schema\Type\ReflectionTypeSchemaResolver` | ні |
| `VO\ResolvedTypeSchemaVO` | `Schema\Type\ResolvedTypeSchema` | ні |

`Interfaces\ArrayConstructibleInterface` і `Interfaces\ArrayConvertibleInterface` теж є аліасами інтерфейсів `Contract\…`; цих імен не було в жодному релізі, лише в робочих комітах.

Видалено без заміни: `Annotations\StrictModeTag`, `Interfaces\Factory\ParamHydratorFactoryInterface`, `Interfaces\Meta\ReflectionMetadataKeyGeneratorInterface`, `Interfaces\Meta\RuntimeReflectionCacheInterface`, `Transformer\Metadata\ClassMetadata`, `Transformer\Metadata\ParameterMetadata`, `Transformer\Metadata\PropertyMetadata`.

### Deprecated-аліаси

`aliases.php` (Composer `autoload.files`) на кожному запиті реєструє `class_alias` для кожного з 21 імені, тож старе ім'я — той самий тип, що й нове: `instanceof`, `catch`, типи параметрів і `is_a()` працюють в обидва боки, а DTO, що реалізує `IArrayConvertible`, проходить перевірку на `ArrayConvertibleInterface`.

Runtime-повідомлення про застарілість немає. Застарілість видно в IDE через `aliases-stubs.php`, який ніколи не завантажується.

```php
use Ufo\DTO\Contract\ArrayConvertibleInterface;
use Ufo\DTO\Interfaces\IArrayConvertible; // deprecated, видаляється в 4.1

$dto instanceof ArrayConvertibleInterface === $dto instanceof IArrayConvertible; // true
```

### Фабрика і validator

`DefaultDTOTransformerFactory::default(?CacheInterface $persistentCache = null, int $runtimeCacheLimit = RuntimeReflectionCache::DEFAULT_LIMIT, ?ValidatorInterface $validator = null)`:

- `$runtimeCacheLimit` — кількість записів у `RuntimeReflectionCache` (2048 за замовчуванням, `0` — без обмеження).
- `$validator` — Symfony validator для `#[AttrAssertions]`. Без нього фабрика створює один лінивий `Validation::createValidator()`, спільний для всіх її трансформерів, під час першої перевірки.
- Кожен виклик `default()` будує новий граф об'єктів з новими кешами; тримайте фабрику (або трансформер) у себе. `create()` повторно використовує внутрішні трансформери фабрики, тож `DTOTransformer` і `ServiceTransformer` з однієї фабрики мають спільні кеші.

```php
$factory = DefaultDTOTransformerFactory::default($cache, validator: $appValidator); // раз на процес
DTOTransformer::boot($factory->create());
ServiceTransformer::boot($factory->create(ServiceTransformer::class));
```

`Helpers\Validator::validate()` приймає необов'язковий четвертий параметр `?ValidatorInterface $validator`.

### Обробка атрибутів

`DTOAttributesEnum` (enum, що працював як сервіс із трьома статичними кешами) замінено двома сервісами, які належать `DTOFromArrayTransformer` і створюються `DefaultDTOTransformerFromArrayFactory`:

- `Transformer\Attribute\AssertionsValidator(ValidatorInterface)` — `validate(AttrAssertions, $value, $reflection)`; validator завжди інжектований.
- `Transformer\Attribute\AttrDTOResolver` — `resolve(AttrDTO, $value, $reflection, DTOFromContextTransformerInterface, TransformationContext)`: `null`, один DTO або enum, колекція, strict-режим, власний трансформер.

`PropertySchema::$attrDto` тримає екземпляр `AttrDTO` (було `ReflectionAttribute`), як і `$assertions`, тож екземпляри атрибутів створюються раз разом зі схемою класу й кеш їм не потрібен. Нащадки `AttrDTO` розпізнаються через `instanceof`.

### Схеми класів і кешування

- `fromArray()` і `toArray()` працюють з однією `Schema\ClassSchema` / `Schema\PropertySchema`, які будує `Schema\ClassSchemaFactory` і надає `Schema\ClassSchemaProviderInterface` (`ClassSchemaProvider`, кешується через `Schema\Cache\CachedClassSchemaProvider`). `Factory\DefaultSchemaFactory::createClassSchemaProvider()` створює його один раз, і `default()` передає той самий провайдер в обидва напрямки.
- Шар схем не залежить від гідраторів. `ClassSchemaProviderInterface::typeSchemas()` повертає `Schema\Type\ResolvedTypeSchema`; бік гідратації компілює їх у вузли свого ланцюга: `Transformer\Hydrator\CompiledTypeSchemaProviderInterface` (`CompiledTypeSchemaProvider`, кешується через `Transformer\Hydrator\Cache\CachedCompiledTypeSchemaProvider`).
- `ClassSchema::$exportedProperties` / `$publicExportedProperties` — `PropertySchema`, які експортує `toArray()` (без `#[Ignore]`); `PropertySchema::$serializationContext` — значення `#[SerializationContext]`, прочитані раз разом зі схемою; `ClassSchema::$requiredKeys` — ключі, без яких гідратація гарантовано падає (їх читає `RequiredKeysCandidateFilter`). `DTONormalizer` і фільтр не тримають власних кешів класів.
- Базові класи не тримають кешів; кешування — у декораторах `Cached*`:

  | Базовий клас | Кеш |
  |---|---|
  | `Schema\ClassSchemaFactory` (`ClassSchemaDescriberInterface`) | `Schema\Cache\CachedClassSchemaDescriber` — persistent-кеш описів |
  | `Schema\ClassSchemaProvider` | `Schema\Cache\CachedClassSchemaProvider` — схеми в пам'яті процесу |
  | `Transformer\Hydrator\CompiledTypeSchemaProvider` | `Transformer\Hydrator\Cache\CachedCompiledTypeSchemaProvider` |
  | `Transformer\Hydrator\HydratorChain` | `Transformer\Hydrator\Cache\CachedHydratorChain` — скомпільовані вузли |

  Застарілий опис у persistent-кеші (клас змінився) перебудовується й перезаписується.
- Стратегія значення за замовчуванням для властивості визначається раз на клас: значення властивості, однойменний необов'язковий параметр конструктора, відсутнє, або шлях на кожен виклик для статичних властивостей і властивостей з хуками.
- Persistent-кеш зберігає описи схем класів (зокрема значення за замовчуванням) і схеми типів для кожного класу. Ключ схеми типів — лише ті ключі namespaces, які клас справді читає, тож класи з нативними типами не залежать від `$namespaces` викликача.
- Кешування винесено в декоратори: `Schema\Meta\Cache\CachedReflectionMetadataProvider`, `CachedStrictModeResolver`, `CachedDocBlockParser`. `ReflectionMetadataProvider`, `StrictModeResolver`, `PhpDocumentorDocBlockParser` і `ReflectionTypeSchemaResolver` — без кешу й з меншою кількістю аргументів конструктора.
- Усі кеші в процесі обмежені: схеми класів 3000, додаткові набори схем типів на клас 32, вузли гідраторів 4096, набори ключів namespaces 1024 (`Helpers\StringListPool`, один масив на однаковий набір), `RuntimeReflectionCache` — `$runtimeCacheLimit`. У 3.1.0 кеш гідраторів і кеш ключів за замовчуванням не мали межі.
- Екземпляри `#[AttrDTO]`, `#[AttrAssertions]` і `#[SerializationContext]` створюються раз на атрибут і зберігаються у `WeakMap`.
- Схеми, побудовані з `'$className'` у payload, не кешуються.

### Точки розширення

- `Transformer\DTOFromContextTransformerInterface::transformFromContext()` — трансформер вкладених DTO отримує об'єкт `TransformationContext` замість масиву.
- `Transformer\DTOCandidateFilterInterface::rejectsData()` — повідомляє, що гідратація класу точно не вдасться, тож `fromArray('A|B')` і union-властивості пробують цей клас останнім. `BaseDTOFromArrayTransformer::rejectsData()` — новий protected static хук (за замовчуванням `false`); `DTOTransformer` делегує його внутрішньому трансформеру.
- `Transformer\Hydrator\SchemaNodeHydratorInterface::resolveNode()` зі `Schema\SchemaNode` — гідратор вкладених схем (union-и, `items`, `additionalProperties`) отримує готовий вузол внутрішньої схеми.
- `DefaultDTOTransformerFromArrayFactory`: protected `createTypeSchemaProvider()`, `createTransformer()`, `createHydrators()`.
- `DefaultSchemaFactory`: `createClassSchemaProvider()`, `createDefaultValueResolver()`, protected `createClassSchemaFactory()`.
- `Schema\ClassSchemaDescriberInterface` — звідки беруться описи схем; `ClassSchemaProvider(ClassSchemaDescriberInterface, ClassSchemaFactory)`.
- `DefaultMetadataFactory`: нові `createMetadataProvider()`, `createStrictModeResolver()`; `createRuntimeReflectionCache()` приймає `int $limit`.
- `Transformer\SmartClassResolver`, `Schema\DefaultValueResolver`, `Schema\RequiredKeysCandidateFilter`, `Schema\Type\TypeCompatibility` — залежності конструктора `DTOFromArrayTransformer`, їх можна замінити.

## Зміни та виправлення

- **`toArray()` враховує `#[Ignore]`.** У 3.x властивості з Symfony `#[Ignore]` експортувались.
- **`ServiceTransformer::toArray()`** повертає весь масив плюс `'$classFQCN'` і передає `$context` далі. У 3.x повертав лише `['$classFQCN' => …]`.
- **`renameKey` у `fromArray()`.** Спершу ключі payload перейменовуються на імена властивостей, далі працює звичайний шлях:
  - відсутній перейменований ключ бере значення за замовчуванням властивості або конструктора (3.1.0: «Missing required key» навіть за наявності значення за замовчуванням);
  - властивість з `#[Ignore]` зберігає значення за замовчуванням, навіть якщо перейменований ключ є в payload (3.1.0: перемагало значення з payload);
  - перейменування в `null` вважає ключ відсутнім (3.1.0: `TypeError`).
- **`#[AttrAssertions]` перевіряє лише наявні ключі.** Відсутній ключ іде до значення за замовчуванням або до «Missing required key»; явний `null` перевіряється як `null`. У 3.1.0 обидва перевірялись як `[]`.
- **`#[AttrDTO]` з `null`**: `null` для nullable або нетипізованої властивості, інакше `BadParamException('Cannot assign null to non-nullable …')`. У 3.1.0: `TypeError` для одного DTO, PHP warning і `[]` для колекції. Не-iterable значення для колекції у strict-режимі кидає `BadParamException`, інакше лишається як є (3.1.0: warning і `[]`).
- **`\ValueError` з обробки атрибута** (наприклад, з вкладеного трансформера) зберігає своє повідомлення. У 3.1.0 будь-який `ValueError` ставав `Unsupported attribute type`.
- **Власний трансформер `#[AttrDTO]`** (`AttrDTO::C_TRANSFORMER`):
  - клас, що не реалізує `StaticDTOFromArrayTransformerInterface`, кидає `NotSupportDTOException('Transformer … must implement …')`; у 3.x його мовчки підміняв трансформер за замовчуванням;
  - «is not support transform for …» називає власний трансформер; у 3.x — трансформер за замовчуванням;
  - він отримує namespaces (викликача й `AttrDTO::C_NS`) четвертим аргументом `fromArray()`; у 3.x — лише `$renameKey`.
- **`?DateTimeInterface` з `null`** лишається `null` (3.1.0: поточний час). Union з `null` повертає `null` для `null`, не пробуючи інших гілок. Обов'язкова дата з `null`, як і раніше, — поточний час.
- **Значення за замовчуванням конструктора з об'єктом і enum** (`Inner $x = new Inner()`, `Status $s = Status::A`) беруться для відсутнього ключа. У 3.1.0 це кидало `Error: Call to a member function matchType() on null`.
- **Ліміт `RuntimeReflectionCache` `0`** означає «без обмеження» (3.1.0: нічого не зберігалось).
- Результат гідратора `null` замінюється сирим значенням на кожен виклик (3.1.0: лише при влучанні в кеш).
- `TransformationContext::withNamespaces()`, `withStrict()`, `withContext()` повертають той самий об'єкт, коли нічого не змінюється.
- **`Validator::throw(string $class = BadParamException::class)`** ігнорував `$class` і завжди кидав `BadParamException` (зокрема в 3.1.0). Тепер кидає `new $class($message)` з поточною помилкою як повідомленням; без аргументу, як і раніше, кидає `BadParamException`.

```php
use Ufo\DTO\Helpers\Validator;
use Symfony\Component\Validator\Constraints\NotBlank;

Validator::validate('', [new NotBlank()])->throw(DomainException::class); // DomainException
Validator::validate('', [new NotBlank()])->throw();                       // BadParamException
```

- **4.0.1**: `DateTimeConverter::DEFAULT_FORMAT` — це `DateTimeInterface::ATOM` (`2026-12-31T10:00:00+00:00`) замість `Y-m-d H:i:s`, тож `toArray()` зберігає часовий пояс. Вхід у старому форматі, як і раніше, розбирається; явний `format` / `output_format` у `#[SerializationContext]` чи контексті, як і раніше, має пріоритет.

### Прибрано

- З `Transformer\DTOFromArrayTransformer`: protected `resolveClassForData()`, `resolveSmartClass()` (→ `SmartClassResolver`), `extractValue()`, `resolveValue()` (→ `resolvePropertyValue()`, `resolveWithSchema()`), `getPropertyValue()`, `canAssignToParameter()` (→ `DefaultValueResolver`, `TypeCompatibility`), `getPromotedConstructorParameter()`, `getPropertyKey()`, `propertyKey()`, `createUninitializedInstance()`, `reflectionClass()`, `reflectionProperties()` і властивість `$defaultPropertyKeys`.
- З `Schema\Meta\ReflectionMetadataProviderInterface`: `classMetadata()`, `properties()`, `propertyMetadata()`, `parameterMetadata()`, `promotedConstructorParameter()`; додано `constructorParameters()`.
- З `Schema\Meta\ReflectionMetadataKeyGenerator`: `namespacesKey()`, `reflectionSchemaKey()`, `nativeTypeKey()`; додано `reflectionKey()`, `declaringClassKey()`.
- `DefaultDTOTransformerFactory::$defaultInstance` (йому ніколи не присвоювалось значення, тож `default()` і так будував новий граф на кожен виклик).

### Зміни конструкторів

| Клас | 3.1.0 | 4.0 |
|---|---|---|
| `DefaultDTOTransformerFromArrayFactory` | `(typeSchemaResolver, metadataProvider, keyGenerator)` | `(typeSchemaResolver, metadataProvider, ClassSchemaProviderInterface $schemaProvider, ValidatorInterface $validator, DateTimeValueConverterInterface $dateTimeValueConverter)` — усі обов'язкові |
| `Transformer\DTOFromArrayTransformer` | `(hydratorChain, typeSchemaResolver, metadataProvider, keyGenerator)` | `(HydratorChainInterface, TypeSchemaResolverInterface, ClassSchemaProviderInterface, CompiledTypeSchemaProviderInterface, DefaultValueResolver, SmartClassResolver, DTOCandidateFilterInterface, AssertionsValidator, AttrDTOResolver)` |
| `Factory\DefaultNormalizerChainFactory` | `DefaultPropertyNormalizerFactory(metadataProvider, contextProvider, converter)` | `(ClassSchemaProviderInterface $schemaProvider, DateTimeValueConverterInterface)` |
| `Transformer\Normalizer\DTONormalizer` | `DtoNormalizer(metadataProvider, contextProvider)` | `(ClassSchemaProviderInterface $schemaProvider, PropertyContextCache = new PropertyContextCache())` |
| `Schema\ClassSchemaFactory` | — (новий у 4.0) | `(ReflectionMetadataProviderInterface, TypeSchemaResolverInterface, DefaultValueResolver, NamespaceKeyAnalyzer, SerializationContextAttributeReaderInterface)` |
| `Schema\Type\ReflectionTypeSchemaResolver` | `(strictModeResolver, metadataProvider, keyGenerator, runtimeCache)` | `(strictModeResolver, metadataProvider)` |
| `Schema\Meta\StrictModeResolver` | `(metadataProvider, keyGenerator, runtimeCache)` | `(metadataProvider)` |
| `Schema\Meta\ReflectionMetadataProvider` | `(docBlockParser, keyGenerator, runtimeCache)` | `(docBlockParser)` |
| `Schema\Meta\PhpDocumentorDocBlockParser` | `(factory, runtimeCache)` | `(factory)` |
| `Transformer\Hydrator\DateTimeHydrator`, `Transformer\Normalizer\DateTimeNormalizer` | `(?DateTimeValueConverterInterface = null)` | конвертер обов'язковий |

`Schema\Type\TypeSchemaResolverInterface` вимагає ще двох методів: `source()` і `fromSource()`.

`SerializationContextProviderInterface` став `Schema\Meta\SerializationContextAttributeReaderInterface` (реалізація `SerializationContextAttributeReader`, без кешу): `contextForProperty(ReflectionProperty, NormalizationContext): NormalizationContext` замінено на `read(ReflectionProperty): array` — reader повертає лише значення `#[SerializationContext]` властивості. `ClassSchemaFactory` викликає його раз на властивість і зберігає результат у `PropertySchema::$serializationContext`; `DTONormalizer` будує з нього контекст новим `NormalizationContext::forPropertyValue()`. Власний reader передається в `DefaultSchemaFactory::createClassSchemaFactory()`, а не в нормалізатор. `DTONormalizer` приймає необов'язковий другий аргумент `Transformer\Normalizer\Cache\PropertyContextCache`: контексти властивостей класу будуються раз на батьківський контекст, тож елементи колекції їх ділять.

### Composer

- `autoload.files: ["aliases.php"]` реєструє deprecated-аліаси.
- `require` той самий, що в 3.1.0. Код використовує API PHP 8.4 (`ReflectionClass::newLazyProxy()`, `array_any()`, `array_all()`).

## Примітки до оновлення

1. **Перейдіть на імена 4.0** (таблиця вище). Імена без аліасу падають одразу — здебільшого це власні гідратори, нормалізатори, фабрики й ручне підключення сервісів `DTOTransformer`. У Symfony треба перейменувати id сервісів і autowiring-аліаси з іменами інтерфейсів 3.x.
2. **21 аліас видаляється в 4.1.** Обмеження на кшталт `^4.0` підтягне 4.1 автоматично, тож перейдіть на нові імена до релізу 4.1.
3. **Рядкові перевірки повертають нове ім'я.** `get_class()`, `class_implements()` і `ReflectionClass::getName()` дають ім'я 4.0, тож `isset(class_implements($dto)['Ufo\DTO\Interfaces\IArrayConvertible'])` — `false`, а `$name === IArrayConstructible::class` більше не збігається з відрефлексованим типом 4.0.
4. **`default($cache, true)`**: приберіть другий аргумент і тримайте фабрику у себе; `runtimeCacheLimit:` і `validator:` передавайте за іменем. Зі `strict_types` старий виклик дає `TypeError`; без нього `true` мовчки стає лімітом кешу 1.
5. **Ручне підключення**: передайте нові аргументи конструкторів (таблиця вище). Для `DefaultDTOTransformerFromArrayFactory` — `ClassSchemaProviderInterface` (з `DefaultSchemaFactory::createClassSchemaProvider()`, той самий і для `DefaultNormalizerChainFactory`), `validator`, `dateTimeValueConverter` замість `keyGenerator`; persistent-кеш тепер передається в `createClassSchemaProvider()`; для `DateTimeHydrator` / `DateTimeNormalizer` — `new DateTimeConverter()`. Ланцюг, створений вручну як `new HydratorChain()` (у 3.x — `ParamHydrator`), компілює схеми на кожен виклик — для кешування, як у 3.x, беріть `CachedHydratorChain`. Краще брати `DefaultMetadataFactory` і `DefaultDTOTransformerFromArrayFactory::create()`, ніж викликати конструктори.
6. **Власні реалізації**: `TypeSchemaResolverInterface` потребує `source()` і `fromSource()`; `ReflectionMetadataProviderInterface` — `constructorParameters()`; `SerializationContextProviderInterface` стає `Schema\Meta\SerializationContextAttributeReaderInterface` з `read()`, що повертає значення, замість `contextForProperty()`, що повертав контекст; він передається в `ClassSchemaFactory`. Типи з прибраними `RuntimeReflectionCacheInterface` / `ReflectionMetadataKeyGeneratorInterface` замініть на конкретні класи.
7. **Нащадки `DTOFromArrayTransformer`**, що використовують прибрані protected-методи: перенесіть логіку в `hydrateProperty()` / `resolvePropertyValue()` або замініть `DefaultValueResolver` / `SmartClassResolver` через `DefaultDTOTransformerFromArrayFactory::createTransformer()`.
8. **`new RuntimeReflectionCache(0)`** тепер без обмеження; для маленького кешу задайте невеликий додатний ліміт.
9. **Очищайте persistent-кеш при деплої.** Ключі не містять версії класу, а тепер там зберігаються схеми класів зі значеннями за замовчуванням.
10. **Перевірте код і тести, що залежать від старих результатів**: `toArray()` пропускає `#[Ignore]`, `ServiceTransformer::toArray()` повертає весь масив, `?DateTime` з `null` — `null`, nullable-колекція `#[AttrDTO]` з `null` — `null` (було `[]`), `#[AttrAssertions]` не спрацьовує на відсутніх ключах, відсутні перейменовані ключі беруть значення за замовчуванням, `ValueError` зберігає повідомлення.
11. **`DTOAttributesEnum` більше немає**: код, що викликав `tryFromAttr()` / `process()`, переходить на `AttrDTOResolver::resolve()` / `AssertionsValidator::validate()` з екземпляром трансформера (статична форма `DTOTransformer::class` не підтримується). Перевірте кожен `AttrDTO::C_TRANSFORMER`: він має реалізувати `StaticDTOFromArrayTransformerInterface`, інакше гідратація тепер падає.
11. **`Validator::throw()` враховує аргумент.** Якщо ви передаєте клас, тепер кидається саме він (раніше — завжди `BadParamException`). Код, що ловить `BadParamException` навколо такого виклику, має ловити переданий клас. Клас має приймати повідомлення першим аргументом конструктора.
12. **4.0.1 змінює формат дат у `toArray()`** на `DATE_ATOM`. Споживач, що розбирає дати строго як `Y-m-d H:i:s`, має приймати ISO 8601 зі зсувом або повернути старий вихід через `#[SerializationContext(['output_format' => 'Y-m-d H:i:s'])]` на властивості.
