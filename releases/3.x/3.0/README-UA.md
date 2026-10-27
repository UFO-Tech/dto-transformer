[English](README.md)

# 3.0 — сервісна архітектура, кешування, DateTime, strict mode

Теги: 3.0.0, 3.0.1, 3.0.4, 3.0.5 (3.0.2 існувала лише як коміт без тегу, її зміна вийшла в 3.0.4; 3.0.3 немає). Перший реліз: 2026-05-25.

## Головне

- **`DTOTransformer` — фасад над замінними сервісами**: ланцюги гідраторів і нормалізаторів, побудова схеми типів і метадані з reflection з runtime LRU-кешем та необов'язковим persistent-кешем (`symfony/cache-contracts`).
- **Статичному API потрібен `boot()`**; з 3.0.4 `TransformerBootstrapper::boot()` ініціалізує `DTOTransformer` і `ServiceTransformer`.
- **DateTime**: гідратація й експорт з контекстом формату й часових поясів.
- **`#[StrictMode]` / `@strictMode`**, `#[SerializationContext]`, `AttrDTO::C_STRICT`, типізовані винятки й параметр `$context` усюди.

## Що нового

### Сервіси й фабрики (3.0.0)

- `DTOTransformer::__construct(Interfaces\DTOFromArrayTransformerInterface $fromArrayTransformer, Interfaces\DTOToArrayTransformerInterface $toArrayTransformer)`; методи екземпляра `transformFromArray()` і `transformToArray()`.
- Статичний життєвий цикл: `DTOTransformer::boot(self $transformer)` (повторний виклик кидає `RuntimeException`), `reset()`, `isInitialized()`. Екземпляри зберігаються окремо для кожного класу, тож `ServiceTransformer` ініціалізується окремо. До того статичні методи кидають `Exceptions\NotInitializeException`.
- Роботу виконують `Transformer\DTOFromArrayTransformer` і `Transformer\DTOToArrayTransformer`.
- `Factory\DefaultDTOTransformerFactory::default(?CacheInterface $persistentCache = null, bool $recreate = false)` і `create(string $class = DTOTransformer::class)`; також `DefaultDTOTransformerFromArrayFactory` (власні `DTOFromArrayTransformerInterface` використовуються для вкладених DTO), `DefaultDTOTransformerToArrayFactory`, `DefaultPropertyNormalizerFactory`, `DefaultMetadataFactory`.
- Гідратори в `Transformer\Hydrator` за інтерфейсом `Interfaces\Hydrator\ParamHydratorInterface` (`supports()`, `resolve()`), ланцюг — `ParamHydrator`. Нормалізатори в `Transformer\Normalizer` за інтерфейсом `Interfaces\Normalizer\PropertyNormalizerInterface`, ланцюг — `PropertyNormalizer`.
- Метадані в `Transformer\Metadata`: `RuntimeReflectionCache` (LRU, 2048 записів за замовчуванням, необов'язковий persistent-шар), `ReflectionMetadataProvider`, `PhpDocumentorDocBlockParser`, `StrictModeResolver`, `AttributeSerializationContextProvider`.

```php
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Ufo\DTO\Factory\DefaultDTOTransformerFactory;

$transformer = DefaultDTOTransformerFactory::default(
    persistentCache: new FilesystemAdapter(namespace: 'dto_transformer'),
)->create();

$dto = $transformer->transformFromArray(UserDto::class, $payload);
```

### `TransformerBootstrapper` (3.0.4, 3.0.5)

`Ufo\DTO\TransformerBootstrapper::boot()` ініціалізує `DTOTransformer` і `ServiceTransformer` з `DefaultDTOTransformerFactory::default()`, кожен лише якщо його ще не ініціалізовано, тож повторний виклик безпечний. `isInitialized()` (3.0.5) — true, коли ініціалізовано обидва.

```php
\Ufo\DTO\TransformerBootstrapper::boot();
```

### DateTime (3.0.0)

`DateTimeImmutable`, `DateTime` і `DateTimeInterface` гідруються з рядка, int або float timestamp. `toArray()` експортує їх рядком, за замовчуванням `Y-m-d H:i:s`. Формат і часові пояси беруться з `#[SerializationContext]` або `context` виклику: `format`, `formats`, `output_format`, `timezone`, `input_timezone`, `output_timezone`, `input` / `output` = `timestamp`. `Transformer\Converter\DateTimeConverter` реалізує `Interfaces\Converter\DateTimeValueConverterInterface`.

### Strict mode (3.0.0)

- Значення з нативним типом розв'язуються строго: невдача кидає `BadParamException("Failed to resolve X::$p: …")`.
- Значення, типізовані лише DocBlock-ом, строгі, коли `#[Attributes\StrictMode]` або тег `@strictMode` знайдено на властивості чи параметрі, функції, класі, його батьках або інтерфейсах. Інакше значення, що не перетворилось, лишається як є.
- `AttrDTO::C_STRICT => true` змушує колекцію падати на поганому елементі; без нього сирий елемент лишається.

```php
#[AttrDTO(UserDto::class, context: [AttrDTO::C_COLLECTION => true, AttrDTO::C_STRICT => true])]
public array $users;
```

### Інші додатки (3.0.0)

- `#[Attributes\SerializationContext(array $context = [])]` на властивостях і параметрах.
- Винятки `InvalidCollectionValueException`, `InvalidEnumValueException`, `InvalidObjectValueException`, `InvalidScalarValueException`, `UnionTypeMismatchException` (усі наслідують `BadParamException`) і `NotInitializeException` (наслідує `RuntimeException`, `fromArray()` його не загортає).
- `Transformer\Converter\EnumConverter::toEnum(string $enumFQCN, string|int $value): UnitEnum`.
- Необов'язковий останній параметр `array $context = []` у `fromArray()`, `toArray()`, `ArrayConstructibleTrait::fromArray()` і `ServiceTransformer`.
- `TypeHintResolver`: кейси `CALLABLE`, `ITERABLE`; `reflectionTypeToSchema()`, `reflectionNamedTypeToSchema()`, `reflectionTypeAllowsArray()`, `isScalarSchema()`, `hasScalarType()`, `isCompositeSchema()`, `scalarTypes()`, `matchType()`.
- Нова залежність `symfony/cache-contracts ^3.5`.

## Зміни та виправлення

- **3.0.0**: значення enum, що не збігається з жодним кейсом, кидає `InvalidEnumValueException` (2.x для чистого enum повертав сире значення); int-backed enum приймає й числові рядки. `EnumResolver::generateEnumSchema()` додає `classFQCN`.
- **3.0.0**: `jsonSchemaToPhp()` / `jsonSchemaToTypeDescription()` пишуть nullable як `null|Type` (було `?Type`).
- **3.0.0**: `AttrDTO::C_TRANSFORMER` використовується, лише якщо клас реалізує `IDTOFromArrayTransformer`; раніше перевірка йшла на трансформері за замовчуванням.
- **3.0.0**: `toArray()` обходить вкладені об'єкти через reflection замість виклику їхнього `IArrayConvertible::toArray()`; `$renameKey` доходить до вкладених рівнів; значення, яке не підтримує жоден нормалізатор, кидає `BadParamException`. `#[Ignore]` у `toArray()` більше не перевіряється.
- **3.0.0**: у `fromArray()` властивість з `#[Ignore]` отримує значення за замовчуванням; без нього лишається неініціалізованою.
- **3.0.1**: int підходить до типу `float`: строга гідратація int у float-властивість і int за замовчуванням для `float $x = 0` приймаються.
- **3.0.4** (з 3.0.2): відносний DocBlock-тип на кшталт `Enums\DeliveryCapabilityEnum` при `use App\SDK\Atlas\Enums;` розв'язується через `use`-аліас першого сегмента.

## Відомі проблеми

Є в усіх релізах 3.x:

- `ServiceTransformer::toArray()` повертає лише `['$classFQCN' => …]` — результат перетворення відкидається. Виправлено в 4.0.
- `toArray()` експортує властивості з `#[Ignore]`. Виправлено в 4.0.
- `DefaultDTOTransformerFactory::default()` будує новий граф об'єктів на кожен виклик.

## Примітки до оновлення

1. **Ініціалізуйте статичний API** на старті: `TransformerBootstrapper::boot()` (3.0.4+) або `DTOTransformer::boot(DefaultDTOTransformerFactory::default()->create())` і те саме для `ServiceTransformer`. Трейти (`ArrayConstructibleTrait`, `ArrayConvertibleTrait`, …) теж використовують статичний API. Без ініціалізації — `NotInitializeException`.
2. **Прибрано з `DTOTransformer`**: `fromSmartArray()` (передавайте дані з `'$className'` у `fromArray()`), `transformEnum()` (беріть `EnumConverter::toEnum()`) і всі protected static хелпери (`extractValue`, `checkAttributes`, `resolveValueForType`, …) — перенесіть власну логіку в гідратор або нормалізатор.
3. **Прибрано `Interfaces\IDTOFromSmartArrayTransformer`**; заміна — `Interfaces\DTOFromSmartArrayTransformerInterface`.
4. **`BaseDTOFromArrayTransformer::transformFromArray()`** — `abstract public static` з п'ятим параметром `array $context = []`. Перевизначення `fromArray()` / `toArray()` мають додати `array $context = []`.
5. **Перевірте зміни поведінки**: DateTime експортується рядком, невідоме значення enum кидає виняток, поганий елемент колекції лишається, якщо немає `C_STRICT`, `#[Ignore]` не враховується в `toArray()`, вкладені `toArray()` не викликаються, nullable-типи пишуться як `null|T`.
