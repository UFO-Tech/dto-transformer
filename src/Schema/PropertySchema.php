<?php

declare(strict_types=1);

namespace Ufo\DTO\Schema;

use ReflectionParameter;
use ReflectionProperty;
use Ufo\DTO\Attributes\AttrAssertions;
use Ufo\DTO\Attributes\AttrDTO;
use Ufo\DTO\Schema\Type\TypeSource;

/**
 * Схема властивості для гідрації, зібрана один раз на клас.
 */
class PropertySchema
{
    /** Ключа немає й значення за замовчуванням немає — помилка. */
    public const int DEFAULT_MISSING = 0;
    /** Значення властивості на екземплярі без конструктора. */
    public const int DEFAULT_PROPERTY = 1;
    /** Значення за замовчуванням однойменного параметра конструктора; обчислюється на кожен виклик. */
    public const int DEFAULT_PARAMETER = 2;
    /** Статична властивість або з hooks — значення обчислюється старим шляхом. */
    public const int DEFAULT_LEGACY = 3;

    /**
     * @param self::DEFAULT_* $defaultKind
     * @param ReflectionParameter|null $fallbackParameter необов'язковий однойменний параметр конструктора для ignored / legacy-значення
     * @param TypeSource|null $typeSource null — для `AttrDTO` або якщо сировину типу не вдалося зібрати
     * @param array<string, mixed> $serializationContext значення `#[SerializationContext]` для `toArray`
     */
    public function __construct(
        readonly public ReflectionProperty $property,
        readonly public string $name,
        readonly public bool $ignored,
        readonly public ?AttrAssertions $assertions,
        readonly public ReflectionProperty|ReflectionParameter $valueReflection,
        readonly public ?AttrDTO $attrDto,
        readonly public int $defaultKind = self::DEFAULT_LEGACY,
        readonly public mixed $defaultValue = null,
        readonly public ?ReflectionParameter $defaultParameter = null,
        readonly public ?ReflectionParameter $fallbackParameter = null,
        readonly public ?TypeSource $typeSource = null,
        readonly public array $serializationContext = [],
    ) {}
}
