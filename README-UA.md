# DTO Transformer

`ufo-tech/dto-transformer` — це PHP 8.3+ бібліотека для перетворення DTO-об'єктів у масиви та гідратації DTO-об'єктів з масивів.

Поточна реалізація побудована навколо замінних сервісів:

- `DTOTransformer` — основний фасад/сервіс.
- `DTOFromArrayTransformer` гідратує масиви в DTO-об'єкти.
- `DTOToArrayTransformer` нормалізує DTO-об'єкти в масиви.
- Реалізації `ParamHydratorInterface` резолвлять типізовані значення під час гідратації.
- Реалізації `PropertyNormalizerInterface` нормалізують значення під час серіалізації.
- Reflection metadata, DocBlocks, strict mode і type schemas централізовані та можуть кешуватися.

## Встановлення

```bash
composer require ufo-tech/dto-transformer
```

Вимоги:

- PHP `>=8.3`
- `ext-intl`
- `symfony/serializer`
- `symfony/validator`
- `symfony/cache-contracts`
- `phpdocumentor/reflection-docblock`
- `phpdocumentor/type-resolver`

## Швидкий Старт

```php
use Ufo\DTO\Factory\DefaultDTOTransformerFactory;

final class UserDto
{
    public function __construct(
        public string $name,
        public string $email,
    ) {}
}

$transformer = DefaultDTOTransformerFactory::default()->create();

$user = $transformer->transformFromArray(UserDto::class, [
    'name' => 'Alex',
    'email' => 'alex@example.com',
]);

$array = $transformer->transformToArray($user);
```

Статичні виклики все ще підтримуються, але перед цим transformer треба ініціалізувати:

```php
use Ufo\DTO\DTOTransformer;
use Ufo\DTO\Factory\DefaultDTOTransformerFactory;

DTOTransformer::boot(DefaultDTOTransformerFactory::default()->create());

$dto = DTOTransformer::fromArray(UserDto::class, $payload);
$array = DTOTransformer::toArray($dto);
```

Якщо статичний фасад використати до `DTOTransformer::boot()`, буде викинуто `NotInitializeException`.

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
- `DefaultDTOTransformerFromArrayFactory`
- `DefaultDTOTransformerToArrayFactory`
- `DefaultPropertyNormalizerFactory`

Для кастомного складання створіть `DefaultDTOTransformerFactory` з власними реалізаціями `DTOTransformerFromArrayFactoryInterface` і `DTOTransformerToArrayFactoryInterface`.

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

Стандартний normalizer chain створюється через `DefaultPropertyNormalizerFactory`:

```php
new PropertyNormalizer([
    new ScalarValueConverter(),
    new EnumNormalizer(),
    new DateTimeNormalizer($dateTimeValueConverter),
    new ArrayNormalizer(),
    new DtoNormalizer($metadataProvider, $serializationContextProvider),
]);
```

Normalizer-и реалізують:

```php
use Ufo\DTO\Interfaces\Normalizer\PropertyNormalizerInterface;
use Ufo\DTO\VO\NormalizationContext;

interface PropertyNormalizerInterface
{
    public function supports(mixed $data, NormalizationContext $context): bool;

    public function normalize(mixed $data, NormalizationContext $context): mixed;
}
```

`DtoNormalizer` читає властивості DTO через metadata, застосовує `renameKey`, `publicOnly`, `SerializationContext`, smart-array output і рекурсивно передає вкладені значення назад у chain.

### Контекст Серіалізації

```php
use DateTimeInterface;
use Ufo\DTO\Attributes\SerializationContext;
use Ufo\DTO\VO\NormalizationContext;

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
$dto = $transformer->transformFromArray(UserDto::class, [
    'name' => 'Alex',
    'email' => 'alex@example.com',
]);
```

Стандартний hydration chain створюється через `DefaultDTOTransformerFromArrayFactory` і `DefaultParamHydratorFactory`:

```php
new UnionParamHydrator();
new EnumParamHydrator();
new ScalarParamHydrator();
new ReflectionClassHydrator();
new ReflectionParameterHydrator();
new ReflectionPropertyHydrator();
new DateTimeHydrator();
new DtoHydrator($transformer, $customTransformers);
new ArrayItemsHydrator();
new AdditionalHydrator();
new MixedHydrator();
```

Hydrator-и реалізують:

```php
use Ufo\DTO\Interfaces\Hydrator\ParamHydratorInterface;
use Ufo\DTO\VO\TransformationContext;

interface ParamHydratorInterface
{
    public function supports(array $schema): bool;

    public function resolve(
        array $schema,
        mixed $value,
        TransformationContext $context,
    ): mixed;
}
```

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

Додайте кастомну поведінку object-to-array через `PropertyNormalizerInterface`:

```php
use Ufo\DTO\Interfaces\Normalizer\PropertyNormalizerInterface;
use Ufo\DTO\VO\NormalizationContext;

final class MoneyNormalizer implements PropertyNormalizerInterface
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

Додайте кастомну поведінку array-to-object через `ParamHydratorInterface`:

```php
use Ufo\DTO\Interfaces\Hydrator\ParamHydratorInterface;
use Ufo\DTO\VO\TransformationContext;

final class UuidHydrator implements ParamHydratorInterface
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

## Інтеграція Із Symfony

Встановіть залежності:

```bash
composer require ufo-tech/dto-transformer
composer require phpdocumentor/reflection-docblock
```

Налаштуйте сервіси:

```yaml
services:
  _defaults:
    autowire: true
    autoconfigure: true

  _instanceof:
    Ufo\DTO\Interfaces\Hydrator\ParamHydratorInterface:
      tags:
        - dto.param_hydrator

    Ufo\DTO\Interfaces\Normalizer\PropertyNormalizerInterface:
      tags:
        - dto.param_normalizer

  Ufo\DTO\:
    resource: '../vendor/ufo-tech/dto-transformer/src'
    exclude:
      - '../vendor/ufo-tech/dto-transformer/src/Annotations/'
      - '../vendor/ufo-tech/dto-transformer/src/Attributes/'

  phpDocumentor\Reflection\DocBlockFactoryInterface:
    class: phpDocumentor\Reflection\DocBlockFactory
    factory: [ 'phpDocumentor\Reflection\DocBlockFactory', 'createInstance' ]

  Ufo\DTO\Interfaces\Normalizer\PropertyNormalizerChainInterface:
    class: Ufo\DTO\Transformer\Normalizer\PropertyNormalizer
    arguments:
      $converters: !tagged_iterator dto.param_normalizer

  Ufo\DTO\Interfaces\DTOFromArrayTransformerInterface:
    factory: [ '@Ufo\DTO\Factory\DefaultDTOTransformerFromArrayFactory', 'create' ]

  Ufo\DTO\Interfaces\DTOToArrayTransformerInterface:
    factory: [ '@Ufo\DTO\Factory\DefaultDTOTransformerToArrayFactory', 'create' ]

  Ufo\DTO\DTOTransformer:
    public: true
    arguments:
      $fromArrayTransformer: '@Ufo\DTO\Interfaces\DTOFromArrayTransformerInterface'
      $toArrayTransformer: '@Ufo\DTO\Interfaces\DTOToArrayTransformerInterface'
```

`public: true` потрібен, якщо transformer отримується через:

```php
$container->get(DTOTransformer::class)
```

або всередині `Bundle::boot()`.

Приклад:

```php
use Ufo\DTO\DTOTransformer;

final class UserService
{
    public function __construct(
        private readonly DTOTransformer $transformer,
    ) {
    }
}
```

```php
$user = $this->transformer->fromArray(UserDTO::class, [
    'name' => 'Alex',
]);

$array = $this->transformer->toArray($user);
```

## Продуктивність

Рекомендації:

- перевикористовуйте один instance `DTOTransformer`;
- передавайте Symfony cache adapter у `DefaultDTOTransformerFactory::default()`;
- надавайте перевагу native types, де це можливо;
- використовуйте DocBlocks для collections і unions, коли native types недостатньо;
- не перебудовуйте custom hydrator або normalizer chains на кожен request.

## Тестування

```bash
docker exec php_dto php vendor/bin/phpunit
```

Бенчмарки:

```bash
composer bench
```

## Ліцензія

MIT
