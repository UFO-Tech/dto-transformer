<?php

namespace Ufo\DTO\VO;

use Ufo\DTO\Helpers\EnumResolver;
use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\StringTransformerEnum;

use function is_int;
use function ucfirst;

readonly class EnumVO
{
    public array $values;

    /**
     * @param string $name
     * @param EnumResolver $type
     * @param array<string,string|int> $values
     */
    public function __construct(
        public string $name,
        public EnumResolver $type,
        array $values
    )
    {
        $vals = [];
        foreach ($values as $key => $value) {
            if (is_int($key)) {
                $key = StringTransformerEnum::transformName($value);
            }
            $vals[$key] = $value;
        }
        $this->values = $vals;
    }

    public static function fromSchema(array $schema, ?string $paramName = null): static
    {
        $data = $schema[EnumResolver::ENUM] ?? [];
        $type = $schema[TypeHintResolver::TYPE] ?? TypeHintResolver::STRING->value;

        return new static(
            $data[EnumResolver::ENUM_NAME] ?? ucfirst($paramName) . 'Enum' ?? throw new \InvalidArgumentException('Enum name is required'),
            EnumResolver::tryFrom($type) ?? EnumResolver::STRING,
            $data[EnumResolver::METHOD_VALUES] ?? $schema[EnumResolver::ENUM_KEY] ?? ['KEY' => 'value'],
        );
    }
}
