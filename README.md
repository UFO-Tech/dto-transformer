## 🧩 **ufo/dto-transformer**

A PHP library that provides tools for **bidirectional transformation between DTO objects ⇄ arrays**, with full type safety, contracts, and flexible conversion logic. Ideal for JSON-RPC, REST APIs, CLI tools, and any context where data is passed as arrays.

---

## 📦 Core Components:

### 🔁 `DTOTransformer`

Central service for:

* transforming arrays into DTOs via `fromArray(...)`;
* serializing DTOs to arrays via `toArray(...)`.

### ⚙️ `IDTOFromArrayTransformer` + `IDTOToArrayTransformer`

Interfaces for custom transformers that encapsulate specific logic for unpacking/packing particular DTOs.

### 🧱 `BaseDTOFromArrayTransformer`

Base class with a default `fromArray()` implementation that includes:

* support check via `supportsClass(...)`;
* key renaming and data normalization;
* constructor argument resolution and instantiation.

### 🚨 `NotSupportDTOException`

Thrown when a transformer does not support the provided DTO class.

---

## 🧬 Contracts & Traits:

### `IArrayConstructible` + `ArrayConstructibleTrait`

For DTO classes that support construction from arrays:

* Maps constructor arguments automatically;
* Works via `ReflectionParameter`.

### `IArrayConvertible` + `ArrayConvertibleTrait`

For DTO classes that can be serialized to arrays:

* Automatically serializes public and readonly properties;
* Supports field aliasing and `#[DTOAttributesEnum::Hidden]`.

---

## 🔌 Usage Example:

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
        #[AttrDTO(User::class, context: [
            AttrDTO::C_COLLECTION => true,
            AttrDTO::C_RENAME_KEYS => ['randomNumber' => null]
        ])]
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

The library supports reading DocBlock annotations for constructors and public DTO properties.  
This makes it possible to accurately detect expected types even if they are not explicitly declared in the signature.

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

🔍 How it works 
- @var and @param annotations are parsed automatically. 
- The library detects union types (UserDto|DummyDTO|IntEnum) and builds the correct collection.
- Supported types:
  - DTO classes (e.g., UserDto, DummyDTO)
  - Enums (e.g., IntEnum)
  - Mixed-type arrays

🚀 Example

When calling DocblockDTO::fromArray($data), the library will automatically:
1.	Convert array elements into the correct DTO or enum.
2.	Ensure type safety according to the DocBlock.
3.	Build a fully initialized object with collections of the required types.


---

## 🔧 Custom Transformer Example

This is a sample **custom transformer** implementing `IDTOFromArrayTransformer` for transforming an `OrderDTO` where `amount` must be cast to float and `createdAt` to `DateTimeImmutable`.

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
    ): object {
        $data['amount'] = (float) $data['amount'];
        $data['createdAt'] = new DateTimeImmutable($data['createdAt']);

        return DTOTransformer::fromArray($classFQCN, $data, $renameKey);
    }

    public static function supportsClass(string $classFQCN): bool
    {
        return is_a($classFQCN, OrderDTO::class, true);
    }
}
```

---

### 🧩 With attribute-based transformer:

```php
use Ufo\DTO\Attributes\AttrDTO;

class MemberWithOrdersDTO implements IArrayConstructible, IArrayConvertible
{
    use ArrayConstructibleTrait;
    use ArrayConvertibleTrait;

    public function __construct(
        public User $user,
        #[AttrDTO(Order::class, context: [
            AttrDTO::C_COLLECTION => true,
            AttrDTO::C_TRANSFORMER => OrderTransformer::class
        ])]
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

$dto = DTOTransformer::fromArray(MemberWithOrdersDTO::class, $data);
```

This transformer:

* strictly follows `IDTOFromArrayTransformer`;
* encapsulates complex conversion logic;
* delegates array-to-object conversion to the core transformer.

---

## 🧠 Library Advantages

* Full support for PHP 8.3 type system;
* Flexible logic via pluggable custom transformers;
* Type-safe, self-descriptive, and composable architecture;
* Simple attribute-based field control without code duplication;
* Standardized DTO handling for SOA and microservices environments.


