
## 🧩 **ufo/dto-transformer**

Бібліотека надає інструменти для **двосторонньої трансформації обʼєктів DTO ⇄ масиви**, з дотриманням типізації, контрактів та гнучкої логіки перетворення. Підходить для використання з JSON-RPC, REST API, CLI та іншими випадками, де дані передаються масивами.

---

## 📦 Основні компоненти:

### 🔁 `DTOTransformer`

Центральний сервіс для:

* трансформації масиву у DTO через `fromArray(...)`;
* серіалізації DTO у масив через `toArray(...)`.

### ⚙️ `IDTOFromArrayTransformer` + `IDTOToArrayTransformer`

Інтерфейси для окремих кастомних трансформерів, які відповідають за специфічну логіку розпакування / пакування конкретних DTO.

### 🧱 `BaseDTOFromArrayTransformer`

Базовий клас із типовою реалізацією `fromArray()`, яка включає:

* перевірку `supportsClass(...)`;
* нормалізацію даних (наприклад, `renameKey`);
* виклик реального конструктора обʼєкта.

### 🚨 `NotSupportDTOException`

Викидається, якщо трансформер не підтримує переданий клас DTO.

---

## 🧬 Контракти та трейти:

### `IArrayConstructible` + `ArrayConstructibleTrait`

Для DTO-класів, які хочуть підтримувати `fromArray(...)`:

* Забезпечують виклик конструктора з відображенням аргументів;
* Працюють з `ReflectionParameter`.

### `IArrayConvertible` + `ArrayConvertibleTrait`

Для DTO-класів, які мають `toArray()`:

* Автоматично серіалізують `public` і `readonly` властивості;
* Підтримують перейменування ключів та `#[DTOAttributesEnum::Hidden]`.

---

## 🔌 Як використовувати:

```php
use Ufo\DTO\Attributes\AttrDTO;

class UserDto implements IArrayConstructible, IArrayConvertible
{
    use ArrayConstructibleTrait;
    use ArrayConvertibleTrait;
    
    public readonly $randomNumber;

    public function __construct(
        public string $name,
        public string $email,
    ) 
    {
        $this->randomNumber = rand(1, 100);   
    }
}

class MemberWithFriendsDTO implements IArrayConstructible, IArrayConvertible
{
    use ArrayConstructibleTrait;
    use ArrayConvertibleTrait;

    public function __construct(
        public User $user
        #[AttrDTO(User::class, collection: true, renameKeys: ['randomNumber' => null])]
        public array $friends
    ) {}
}

$data = [
    'user' => [
        'name' => 'Alex',
        'email' => 'alex@site.com',
        'randomNumber' => 99,
    ],
    'friends' => [
        [
            'name' => 'Ivan',
            'email' => 'ivan@site.com',
            'randomNumber' => 23,
        ],
        [
            'name' => 'Peter',
            'email' => 'peter@site.com',
            'randomNumber' => 14,
        ]
    ]
];

$dto = DTOTransformer::fromArray(MemberWithFriendsDTO::class, $data);
var_dump($dto);
//object(MemberWithFriendsDTO)#...
//  public $user =>
//    object(User)#...
//      public $name => "Alex"
//      public $email => "alex@site.com"
//      public $randomNumber => 12
//
//  public $friends =>
//    array(2) {
//      [0] =>
//        object(User)#...
//          public $name => "Ivan"
//          public $email => "ivan@site.com"
//          public $randomNumber => 23
//      [1] =>
//        object(User)#...
//          public $name => "Peter"
//          public $email => "peter@site.com"
//          public $randomNumber => 11
//    }

$data = DTOTransformer::toArray($dto); 
//[
//    'user' => [
//        'name' => 'Alex',
//        'email' => 'alex@site.com',
//        'randomNumber' => 12,
//    ],
//    'friends' => [
//        [
//            'name' => 'Ivan',
//            'email' => 'ivan@site.com',
//            'randomNumber' => 23,
//        ],
//        [
//            'name' => 'Peter',
//            'email' => 'peter@site.com',
//            'randomNumber' => 11,
//        ]
//    ]
//];
```

# 📖 DocBlock Support

Бібліотека підтримує зчитування DocBlock-анотацій для конструкторів і публічних властивостей DTO.
Це дозволяє точно визначати очікувані типи навіть тоді, коли вони не вказані безпосередньо в сигнатурі.

```php
    use Ufo\DTO\Tests\Fixtures\Enum\IntEnum;

    class DocblockDTO
    {
        /**
         * @var array<UserDto|DummyDTO>
         */
        public array $formatedCollection = [];
    
        /**
         * @param array<UserDto|DummyDTO|IntEnum> $collection
         */
        public function __construct(
            public string $name,
            public array $collection
        ) {}
    }
```

🔍 Як це працює
- Анотації @var та @param аналізуються автоматично. 
- Бібліотека визначає union-типи (UserDto|DummyDTO|IntEnum) та будує правильну колекцію. 
- Підтримуються:
  - DTO класи (наприклад, UserDto, DummyDTO)
  - enum-и (наприклад, IntEnum)
  - масиви змішаних типів

🚀 Приклад

При виклику DocblockDTO::fromArray($data) бібліотека автоматично:
1.	Конвертує елементи масиву в потрібні DTO або enum.
2.	Гарантує відповідність типів згідно з DocBlock.
3.	Побудує повноцінний об’єкт із колекціями потрібних типів.

---

## 🔧 Кастомний трансформер:

Ось приклад **кастомного трансформера**, який реалізує `IDTOFromArrayTransformer`, і перетворює DTO `OrderDTO`, у якого, наприклад, поле `amount` потрібно кастити в `float`, а `createdAt` — в `DateTimeImmutable`.

```php
use Ufo\RpcObject\DTO\IDTOFromArrayTransformer;
use Ufo\RpcObject\DTO\DTOTransformer;

class OrderDTO
{
    public function __construct(
        public int $id,
        public float $amount,
        public DateTimeImmutable $createdAt,
    ) {}
}


final class OrderTransformer implements IDTOFromArrayTransformer
{
    public static function fromArray(
        string $classFQCN,
        array $data,
        array $renameKey = []
    ): object 
    {
        $data['amount'] = (float) $data['amount'];
        $data['createdAt'] = new DateTimeImmutable($data['createdAt']);

        return DTOTransformer::fromArray($classFQCN, $data, $renameKey);
    }

    public static function supportsClass(string $classFQCN): bool
    {
        return is_a($classFQCN, OrderDTO::class, true);
    }
}

$data = [
    'id' => 101,
    'amount' => '199.90',
    'createdAt' => '2025-05-09T20:00:00+03:00'
];

$dto = OrderTransformer::fromArray(OrderDTO::class, $data);

```

Або:
```php
use Ufo\DTO\Attributes\AttrDTO;

class MemberWithOrdersDTO implements IArrayConstructible, IArrayConvertible
{
    use ArrayConstructibleTrait;
    use ArrayConvertibleTrait;

    public function __construct(
        public User $user
        #[AttrDTO(Order::class, collection: true, transformerFQCN: OrderTransformer::class)]
        public array $orders
    ) {}
}


$data = [
    'user' => [
        'name' => 'Alex',
        'email' => 'alex@site.com',
    ],
    "orders" => [
        [
            'id' => 101,
            'amount' => '199.90',
            'createdAt' => '2025-05-09T20:00:00+03:00'
        ],
        [
            'id' => 102,
            'amount' => '99.90',
            'createdAt' => '2025-05-08T12:20:00+03:00'
        ]
    ]
];

$dto = DTOTransformer::fromArray(OrderDTO::class, $data);

```
---

Цей трансформер:

* не порушує контракт `IDTOFromArrayTransformer`;
* централізує перетворення складних типів;
* використовує `DTOTransformer::fromArray` як ядро.


---

## 🧠 Переваги бібліотеки:

* Повна підтримка PHP 8.3 типізації;
* Гнучка логіка трансформації через підтримку специфічних трансформерів;
* Безпечна архітектура з чітким контролем типів;
* Просте додавання атрибутів / перейменування полів без дублювання логіки;
* Уніфікація обробки DTO в SOA / мікросервісній архітектурі.
