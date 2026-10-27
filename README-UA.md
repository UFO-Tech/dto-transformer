# DTO Transformer

`ufo-tech/dto-transformer` — це PHP 8.4+ бібліотека для перетворення DTO-об'єктів у масиви та гідратації DTO-об'єктів з масивів.

Поточна реалізація побудована навколо замінних сервісів:

- `DTOTransformer` — основний фасад/сервіс.
- `DTOFromArrayTransformer` гідратує масиви в DTO-об'єкти.
- `DTOToArrayTransformer` нормалізує DTO-об'єкти в масиви.
- Реалізації `HydratorInterface` резолвлять типізовані значення під час гідратації.
- Реалізації `NormalizerInterface` нормалізують значення під час серіалізації.
- Reflection metadata, DocBlocks, strict mode і type schemas централізовані та можуть кешуватися.

## Встановлення

```bash
composer require ufo-tech/dto-transformer
```

Вимоги:

- PHP `>=8.4`
- `ext-intl`
- `symfony/serializer`
- `symfony/validator`
- `symfony/cache-contracts`
- `phpdocumentor/reflection-docblock`
- `phpdocumentor/type-resolver`

## Швидкий Старт

```php
use Ufo\DTO\DTOTransformer;
use Ufo\DTO\Factory\DefaultDTOTransformerFactory;

final class UserDto
{
    public function __construct(
        public string $name,
        public string $email,
    ) {}
}

DTOTransformer::boot(DefaultDTOTransformerFactory::default()->create());

$user = DTOTransformer::fromArray(UserDto::class, [
    'name' => 'Alex',
    'email' => 'alex@example.com',
]);

$array = DTOTransformer::toArray($user);
```

Ініціалізуйте transformer один раз на процес. `DTOTransformer::fromArray()`, `DTOTransformer::toArray()` і `DTOTransformer::transformFromArray()` статичні й використовують ініціалізований екземпляр; до `DTOTransformer::boot()` вони кидають `NotInitializeException`. `transformToArray()` — метод екземпляра й працює на будь-якому створеному transformer.

## Стандартні Фабрики

Використовуйте `DefaultDTOTransformerFactory::default()` для стандартного складання сервісів:

```php
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Ufo\DTO\Factory\DefaultDTOTransformerFactory;

$cache = new FilesystemAdapter(namespace: 'dto_transformer');

$transformer = DefaultDTOTransformerFactory::default(
    persistentCache: $cache,
)->create();
```

Стандартна фабрика створює та зв'язує:

- `RuntimeReflectionCache`
- `ReflectionMetadataProvider`
- `StrictModeResolver`
- `ReflectionTypeSchemaResolver`
- один провайдер схем класів (`DefaultSchemaFactory::createClassSchemaProvider()`), спільний для обох напрямків
- `DefaultDTOTransformerFromArrayFactory`
- `DefaultDTOTransformerToArrayFactory`
- `DefaultNormalizerChainFactory`

Transformer-и, створені однією фабрикою, ділять один граф і його кеші, тож `DTOTransformer` і `ServiceTransformer` створюйте з однієї фабрики:

```php
$factory = DefaultDTOTransformerFactory::default(persistentCache: $cache);

DTOTransformer::boot($factory->create());
ServiceTransformer::boot($factory->create(ServiceTransformer::class));
```

Для кастомного складання створіть `DefaultDTOTransformerFactory` з власними реалізаціями `DTOTransformerFromArrayFactoryInterface` і `DTOTransformerToArrayFactoryInterface`.

Необов'язкові аргументи `default()`:

- `persistentCache` — Symfony cache для reflection-метаданих і схем класів;
- `runtimeCacheLimit` — розмір кешу метаданих у пам'яті процесу (за замовчуванням `2048` записів; `0` вимикає ліміт, і кеш росте без меж);
- `validator` — Symfony `ValidatorInterface` для `AttrAssertions` (див. нижче).

`default()` на кожен виклик збирає нову композицію з порожніми кешами в пам'яті. Створіть transformer один раз і перевикористовуйте його: зробіть `boot()` один раз або зареєструйте як shared service.

## Об'єкт У Масив

```php
$array = $transformer->transformToArray(
    dto: $dto,
    renameKey: ['name' => 'full_name'],
    asSmartArray: false,
    publicOnly: true,
    context: [],
);
```

Стандартний normalizer chain створюється через `DefaultNormalizerChainFactory`:

```php
new NormalizerChain([
    new ScalarNormalizer(),
    new EnumNormalizer(),
    new DateTimeNormalizer($dateTimeValueConverter),
    new ArrayNormalizer(),
    new DTONormalizer($schemaProvider, new PropertyContextCache()),
]);
```

Normalizer-и реалізують:

```php
use Ufo\DTO\Transformer\Normalizer\NormalizerInterface;
use Ufo\DTO\Transformer\Context\NormalizationContext;

interface NormalizerInterface
{
    public function supports(mixed $data, NormalizationContext $context): bool;

    public function normalize(mixed $data, NormalizationContext $context): mixed;
}
```

`DTONormalizer` читає властивості DTO через metadata, застосовує `renameKey`, `publicOnly`, `SerializationContext`, smart-array output і рекурсивно передає вкладені значення назад у chain.

### Контекст Серіалізації

```php
use DateTimeInterface;
use Ufo\DTO\Attributes\SerializationContext;
use Ufo\DTO\Transformer\Context\NormalizationContext;

final class EventDto
{
    public function __construct(
        #[SerializationContext([
            NormalizationContext::DATE_FORMAT => DateTimeInterface::ATOM,
            NormalizationContext::DATE_TIMEZONE => 'UTC',
        ])]
        public DateTimeImmutable $createdAt,
    ) {}
}
```

Стандартний enum output:

- backed enum-и нормалізуються у backing value;
- unit enum-и нормалізуються в назву case.

Стандартний DateTime формат — `Y-m-d H:i:s`. Використовуйте `SerializationContext` або call-level `context`, щоб змінити формат дати, timezone або timestamp output.

## Масив В Об'єкт

```php
$dto = DTOTransformer::fromArray(
    UserDto::class,
    data: ['full_name' => 'Alex', 'email' => 'alex@example.com'],
    renameKey: ['name' => 'full_name'],
    namespaces: [],
    context: [],
);
```

`renameKey` зіставляє властивість із ключем, під яким вона приходить у payload. Перейменовані властивості гідруються так само, як інші: відсутній перейменований ключ бере значення за замовчуванням, `#[Ignore]` діє, а перейменування в `null` означає, що ключа немає. Повідомлення про відсутній обов'язковий ключ називає ключ із payload.

Якщо в payload немає ключа властивості, береться значення за замовчуванням: значення властивості, значення параметра конструктора (зокрема об'єкти на кшталт `new Address()`, які створюються для кожного гідрованого об'єкта), інакше гідратація падає з `Missing required key for property`.

Стандартний hydration chain створюється через `DefaultDTOTransformerFromArrayFactory`:

```php
new UnionHydrator();
new EnumHydrator();
new ScalarHydrator();
new ReflectionClassHydrator();
new ReflectionParameterHydrator();
new ReflectionPropertyHydrator();
new DateTimeHydrator($dateTimeValueConverter);
new DTOHydrator($transformer, $customTransformers);
new ArrayItemsHydrator();
new AdditionalHydrator();
new MixedHydrator();
```

Hydrator-и реалізують:

```php
use Ufo\DTO\Transformer\Hydrator\HydratorInterface;
use Ufo\DTO\Transformer\Context\TransformationContext;

interface HydratorInterface
{
    public function supports(array $schema): bool;

    public function resolve(
        array $schema,
        mixed $value,
        TransformationContext $context,
    ): mixed;
}
```

Schemas компілюються один раз у дерева `SchemaNode`: кожен вузол тримає обраний hydrator і скомпільовані дочірні `items`, `additionalProperties` та `oneOf`. Hydrator вкладених schemas може реалізувати `SchemaNodeHydratorInterface::resolveNode()`, щоб брати ці дочірні вузли, а не вибирати hydrator на кожне значення; так роблять `ArrayItemsHydrator`, `AdditionalHydrator` і `UnionHydrator`.

`ReflectionTypeSchemaResolver` будує schemas з native PHP types, DocBlocks, `AttrDTO`, namespaces і strict-mode metadata. Якщо schema недоступна, оригінальне значення лишається без змін.

### Нативні Типи

```php
final class ProfileDto
{
    public function __construct(
        public string $name,
        public int $age,
        public bool $active,
    ) {}
}
```

### Вкладені DTO

```php
final class OrderDto
{
    public function __construct(
        public UserDto $user,
    ) {}
}

$order = DTOTransformer::fromArray(OrderDto::class, [
    'user' => [
        'name' => 'Alex',
        'email' => 'alex@example.com',
    ],
]);
```

### Колекції

Колекції можна описувати через DocBlocks:

```php
final class TeamDto
{
    /**
     * @param UserDto[] $users
     */
    public function __construct(
        public array $users,
    ) {}
}
```

Або через `AttrDTO`:

```php
use Ufo\DTO\Attributes\AttrDTO;

final class TeamDto
{
    public function __construct(
        #[AttrDTO(UserDto::class, context: [
            AttrDTO::C_COLLECTION => true,
        ])]
        public array $users,
    ) {}
}
```

### Smart Arrays

Smart arrays додають назву DTO-класу в payload:

```php
$array = DTOTransformer::toArray($dto, asSmartArray: true);

// [
//     'name' => 'Alex',
//     '$className' => App\Dto\UserDto::class,
// ]
```

Їх можна відновити через namespace aliases:

```php
$dto = DTOTransformer::fromSmartArray($array, namespaces: [
    DTOTransformer::DTO_NS_KEY => App\Dto::class,
]);
```

### Namespaces

`namespaces` зіставляє короткі назви класів із DocBlocks, smart arrays і `fromArray('A|B')` з класами або namespace: `['Item' => App\Dto\Item::class]`, `[DTOTransformer::DTO_NS_KEY => 'App\Dto']`. Namespaces класу, який гідратується, мають пріоритет над переданими викликачем.

Передавайте namespaces зі скінченного набору, визначеного в коді чи конфігурації, а не з payload чи даних конкретного запиту. Для класів, типи яких залежать від namespaces (короткі назви класів у DocBlocks, нативні object-типи, що не є класами), схеми гідратації кешуються на кожну комбінацію значень namespaces: кеші в пам'яті обмежені, але кожна нова комбінація розв'язується заново й додає записи в persistent cache.

### DateTime

Гідратація підтримує `DateTimeImmutable`, `DateTime`, `DateTimeInterface`, strings, integer timestamps і float timestamps з microseconds.

```php
final class EventDto
{
    public function __construct(
        public DateTimeImmutable $createdAt,
    ) {}
}

$dto = DTOTransformer::fromArray(EventDto::class, [
    'createdAt' => '2026-05-08 14:30:00',
]);
```

### Enums

Backed enums використовують `tryFrom()`. Integer-backed enums також приймають numeric strings. Unit enums резолвляться за назвою case без урахування регістру.

```php
enum Status: string
{
    case Active = 'active';
}

final class UserDto
{
    public function __construct(
        public Status $status,
    ) {}
}
```

## Атрибути

### `AttrDTO`

`AttrDTO` задає явні підказки для denormalization:

```php
use Ufo\DTO\Attributes\AttrDTO;

final class WrapperDto
{
    public function __construct(
        #[AttrDTO(UserDto::class, context: [
            AttrDTO::C_COLLECTION => true,
            AttrDTO::C_STRICT => true,
        ])]
        public array $users,
    ) {}
}
```

Підтримувані context keys:

- `AttrDTO::C_COLLECTION`
- `AttrDTO::C_STRICT`
- `AttrDTO::C_NS`
- `AttrDTO::C_RENAME_KEYS`
- `AttrDTO::C_TRANSFORMER`
- `AttrDTO::C_IS_ENUM`
- `AttrDTO::C_PROPERTY`

Клас, переданий у `AttrDTO::C_TRANSFORMER`, має реалізовувати `StaticDTOFromArrayTransformerInterface`.

### `AttrAssertions`

`AttrAssertions` перевіряє сире значення з payload обмеженнями Symfony Validator до гідратації; порушення кидає `BadParamException`. Обмеження застосовуються, лише якщо ключ є в payload: відсутній ключ бере значення за замовчуванням (або падає як відсутній обов'язковий ключ), явний `null` перевіряється як `null`:

```php
use Symfony\Component\Validator\Constraints as Assert;
use Ufo\DTO\Attributes\AttrAssertions;

final class UserDto
{
    public function __construct(
        #[AttrAssertions([new Assert\NotBlank(), new Assert\Length(min: 3)])]
        public string $name,
    ) {}
}
```

Validator застосунку (з кастомними constraint validators і перекладами) передавайте через фабрику:

```php
$transformer = DefaultDTOTransformerFactory::default(validator: $validator)->create();
```

Без нього `default()` дає один лінивий validator за замовчуванням (`Validation::createValidator()`), спільний для всіх transformer-ів фабрики; він створюється при першій перевірці `AttrAssertions`.

### `StrictMode`

Strict mode перетворює помилки гідратації на `BadParamException`. Без strict mode невалідні вкладені значення можуть залишатися як є.

```php
use Ufo\DTO\Attributes\StrictMode;

final class UserDto
{
    public function __construct(
        #[StrictMode]
        public int $age,
    ) {}
}
```

DocBlock strict mode також підтримується:

```php
/**
 * @strictMode true
 */
final class UserDto
{
}
```

## Розширення

Додайте кастомну поведінку object-to-array через `NormalizerInterface`:

```php
use Ufo\DTO\Transformer\Normalizer\NormalizerInterface;
use Ufo\DTO\Transformer\Context\NormalizationContext;

final class MoneyNormalizer implements NormalizerInterface
{
    public function supports(mixed $data, NormalizationContext $context): bool
    {
        return $data instanceof Money;
    }

    public function normalize(mixed $data, NormalizationContext $context): string
    {
        return $data->currency . ' ' . number_format($data->amount / 100, 2);
    }
}
```

Додайте кастомну поведінку array-to-object через `HydratorInterface`:

```php
use Ufo\DTO\Transformer\Hydrator\HydratorInterface;
use Ufo\DTO\Transformer\Context\TransformationContext;

final class UuidHydrator implements HydratorInterface
{
    public function supports(array $schema): bool
    {
        return ($schema['format'] ?? null) === 'uuid';
    }

    public function resolve(array $schema, mixed $value, TransformationContext $context): Uuid
    {
        return Uuid::fromString((string) $value);
    }
}
```

Для повного контролю надайте власні factory implementations або створіть `DTOFromArrayTransformer` / `DTOToArrayTransformer` з кастомними chains.

Необов'язкові інтерфейси для власної реалізації `DTOFromArrayTransformerInterface`:

- `DTOFromContextTransformerInterface` — отримує для вкладених DTO об'єкт `TransformationContext`, а не перебудований масив;
- `DTOCandidateFilterInterface` — дешево відсіює кандидатів union (`fromArray('A|B')`, властивості `A|B`), гідратація яких гарантовано впаде, щоб не гідрувати їх навмання. Повертайте `true`, лише коли невдача гарантована.

## Інтеграція Із Symfony

Встановіть пакет:

```bash
composer require ufo-tech/dto-transformer
```

Будуйте transformer-и через `DefaultDTOTransformerFactory` — та сама композиція, що й поза Symfony:

```yaml
services:
  ufo.dto.cache:
    class: Symfony\Component\Cache\Adapter\FilesystemAdapter
    arguments:
      $namespace: 'dto_transformer'
      $directory: '%kernel.cache_dir%/dto-transformer'

  Ufo\DTO\Factory\DefaultDTOTransformerFactory:
    factory: [ 'Ufo\DTO\Factory\DefaultDTOTransformerFactory', 'default' ]
    arguments:
      $persistentCache: '@ufo.dto.cache'
      $validator: '@validator'

  Ufo\DTO\DTOTransformer:
    public: true
    factory: [ '@Ufo\DTO\Factory\DefaultDTOTransformerFactory', 'create' ]

  Ufo\DTO\ServiceTransformer:
    public: true
    factory: [ '@Ufo\DTO\Factory\DefaultDTOTransformerFactory', 'create' ]
    arguments: [ 'Ufo\DTO\ServiceTransformer' ]
```

- `$persistentCache` необов'язковий: приберіть аргумент і сервіс `ufo.dto.cache`, щоб кеш був лише в пам'яті. Persistent-кеш очищайте при деплої.
- `$validator: '@validator'` — `AttrAssertions` використовує validator застосунку (потрібен `framework.validation`). Без нього фабрика створює власний validator за замовчуванням.
- Один сервіс фабрики — `DTOTransformer` і `ServiceTransformer` мають спільний граф об'єктів і кеші. Якщо `ServiceTransformer` не потрібен, приберіть його.

`public: true` потрібен, якщо transformer отримується через:

```php
$container->get(DTOTransformer::class)
```

або всередині `Bundle::boot()`. Статичні методи (`fromArray()`, `toArray()`) працюють лише після того, як сервіс із контейнера один раз ініціалізовано, наприклад у `Bundle::boot()`:

```php
if (!DTOTransformer::isInitialized()) {
    DTOTransformer::boot($this->container->get(DTOTransformer::class));
}
if (!ServiceTransformer::isInitialized()) {
    ServiceTransformer::boot($this->container->get(ServiceTransformer::class));
}
```

Приклад з інжектованим transformer-ом — викликайте методи екземпляра:

```php
use Ufo\DTO\DTOTransformer;

final class UserService
{
    public function __construct(
        private readonly DTOTransformer $transformer,
    ) {
    }

    public function handle(array $payload): array
    {
        $user = $this->transformer->transformFromArray(UserDTO::class, $payload);

        return $this->transformer->transformToArray($user);
    }
}
```

Статичні `DTOTransformer::fromArray()` / `toArray()` завжди працюють з ініціалізованим екземпляром, а не з інжектованим.

## Продуктивність

Рекомендації:

- перевикористовуйте один instance `DTOTransformer`: `DefaultDTOTransformerFactory::default()` на кожен виклик створює нові кеші;
- створюйте `DTOTransformer` і `ServiceTransformer` з однієї фабрики: вони ділять схеми класів;
- передавайте Symfony cache adapter у `DefaultDTOTransformerFactory::default()`;
- надавайте перевагу native types, де це можливо: властивості лише з native types не залежать від namespaces викликача;
- використовуйте DocBlocks для collections і unions, коли native types недостатньо;
- передавайте namespaces із фіксованого набору (див. [Namespaces](#namespaces));
- не перебудовуйте custom hydrator або normalizer chains на кожен request.

Кеші в пам'яті процесу обмежені, тож довгоживучі воркери тримають стабільний обсяг пам'яті: схеми класів — до 3000 класів, інші кеші на клас — до 1024, кеш метаданих — `runtimeCacheLimit` записів.

## Тестування

```bash
docker exec php_dto php vendor/bin/phpunit
```

Бенчмарки (час, CPU, дисковий I/O, виділена й утримана пам'ять, ріст пам'яті):

```bash
docker exec php_dto composer bench                                  # warm, таблиця
docker exec php_dto composer bench -- --mode=reset                  # свіжий граф трансформерів перед кожним заміром
docker exec php_dto composer bench -- --mode=cold-process           # перший виклик у новому PHP-процесі
docker exec php_dto composer bench -- --mode=cold-persistent        # те саме із заповненим persistent-кешем
docker exec php_dto composer bench -- --mode=cold-persistent-empty  # те саме з порожнім persistent-кешем (перший запуск після деплою)
docker exec php_dto composer bench -- --mode=growth                 # пам'ять довгоживучого процесу
docker exec php_dto composer bench -- --filter=fromArray --note="що і навіщо"
docker exec php_dto php benchmarks/compare.php benchmarks/results/<запуск-a> benchmarks/results/<запуск-b>
```

Кожен запуск зберігається в `benchmarks/results/<дата>_<режим>_php<версія>_opcache-…_jit-…_xdebug-…_<коміт>[-dirty]/`:
`report.md` (команда, нотатка, середовище, таблиці) і `results.json` (сирі заміри). Git теку не трекає;
`--no-save` вимикає збереження.

Методологія: скіл `ufo-tech/benchmark-author` і правило `ufo-tech/php-benchmarks` каталогу UFO Tech.
Колонки I/O потребують Linux (`/proc/self/io`); на інших системах звіт показує їх у «Not measured».

## Ліцензія

MIT
