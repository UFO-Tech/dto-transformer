
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

## 🧾 `DTOAttributesEnum`

Enum, який визначає атрибути властивостей, наприклад:

* `Hidden` — не включати у toArray;
* `Alias` — альтернативна назва поля.

---

## 🔌 Як використовувати:

```php
class MyDTO implements IArrayConstructible, IArrayConvertible
{
    use ArrayConstructibleTrait;
    use ArrayConvertibleTrait;

    public function __construct(
        public readonly string $id,
        public string $name
    ) {}
}

$dto = DTOTransformer::fromArray(MyDTO::class, ['id' => '123', 'name' => 'Alex']);
$data = DTOTransformer::toArray($dto);
```

---

## 🧠 Переваги:

* Повна підтримка PHP 8.3 типізації;
* Гнучка логіка трансформації через підтримку специфічних трансформерів;
* Безпечна архітектура з чітким контролем типів;
* Просте додавання атрибутів / перейменування полів без дублювання логіки;
* Уніфікація обробки DTO в SOA / мікросервісній архітектурі.
