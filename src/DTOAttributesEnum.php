<?php

namespace Ufo\DTO;

use ReflectionAttribute;
use ReflectionParameter;
use ReflectionProperty;
use Ufo\DTO\Attributes\AttrDTO;
use Ufo\DTO\Attributes\AttrAssertions;
use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Exceptions\NotSupportDTOException;
use Ufo\DTO\Helpers\Validator;
use Ufo\DTO\Interfaces\IDTOFromArrayTransformer;

use function class_implements;
use function class_parents;

enum DTOAttributesEnum: string
{
    case ASSERTIONS = AttrAssertions::class;
    case DTO = AttrDTO::class;

    /**
     * @psalm-param class-string<IDTOFromArrayTransformer> $dtoTransformerFQCN
     */
    public static function tryFromAttr(
        ReflectionAttribute $attributeDefinition,
        mixed $value,
        ReflectionProperty|ReflectionParameter $property,
        string $dtoTransformerFQCN
    ): mixed
    {
        $attribute = $attributeDefinition->newInstance();
        try {
            return self::from($attributeDefinition->name)->process($attribute, $value, $property, $dtoTransformerFQCN);
        } catch (\ValueError) {
            foreach (class_parents($attribute) as $parentAttribute) {
                try {
                    return self::from($parentAttribute)->process($attribute, $value, $property, $dtoTransformerFQCN);
                } catch (\ValueError) {}
            }
            throw new \ValueError('Unsupported attribute type');
        }
    }

    /**
     * @psalm-param class-string<IDTOFromArrayTransformer> $dtoTransformerFQCN
     */
    public function process(
        object $attribute,
        mixed $value,
        ReflectionProperty|ReflectionParameter $property,
        string $dtoTransformerFQCN
    ): mixed
    {
        return match ($this) {
            self::ASSERTIONS => $this->validate($attribute, $value, $property),
            self::DTO => $this->resolveDTO($attribute, $value, $dtoTransformerFQCN),
        };
    }

    /**
     * @psalm-param class-string<IDTOFromArrayTransformer> $dtoTransformerFQCN
     */
    protected function resolveDTO(AttrDTO $attribute, mixed $value, string $dtoTransformerFQCN): array|object
    {
        if ($attribute->collection) {
            return $this->transformDTOCollection($attribute, $value, $dtoTransformerFQCN);
        }
        return $this->transformDto($attribute, $value, $dtoTransformerFQCN);
    }

    /**
     * @psalm-param class-string<IDTOFromArrayTransformer> $dtoTransformerFQCN
     */
    protected function transformDTOCollection(AttrDTO $attribute, mixed $value, string $dtoTransformerFQCN): array
    {
        $result = [];
        foreach ($value as $key => $item) {
            $result[$key] = $this->transformDto($attribute, $item, $dtoTransformerFQCN);
        }
        return $result;
    }

    /**
     * @psalm-param class-string<IDTOFromArrayTransformer> $dtoTransformerFQCN
     * @throws BadParamException
     * @throws NotSupportDTOException
     */
    protected function transformDto(
        AttrDTO $attribute,
        mixed $value,
        string $dtoTransformerFQCN
    ): object
    {
         if ($customDTOTransformerFQCN = $attribute->transformerFQCN) {
             $implements = class_implements($dtoTransformerFQCN);
             if ($implements[IDTOFromArrayTransformer::class] ?? false) {
                 /**
                  * @var IDTOFromArrayTransformer $customDTOTransformerFQCN
                  */
                 if (!$customDTOTransformerFQCN::isSupportClass($attribute->dtoFQCN)) {
                     throw new NotSupportDTOException($dtoTransformerFQCN . ' is not support transform for ' . $attribute->dtoFQCN);
                 }
                 return $customDTOTransformerFQCN::fromArray($attribute->dtoFQCN, $value, $attribute->renameKeys);
             }
         }
        return $dtoTransformerFQCN::fromArray($attribute->dtoFQCN, $value, $attribute->renameKeys);
    }

    protected function validate(AttrAssertions $attribute, mixed $value, ReflectionProperty|ReflectionParameter $property): mixed
    {
        $assertions = $attribute->assertions;
        $validator = Validator::validate($value, $assertions);

        if ($validator->hasErrors()) {
            $errorMessage = $property->getName() . $validator->getCurrentError();
            throw new BadParamException($errorMessage);
        }
        return $value;
    }
}
