<?php

namespace Ufo\DTO;

use ReflectionAttribute;
use ReflectionParameter;
use ReflectionProperty;
use Throwable;
use Ufo\DTO\Attributes\AttrAssertions;
use Ufo\DTO\Attributes\AttrDTO;
use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Exceptions\NotSupportDTOException;
use Ufo\DTO\Helpers\Validator;
use Ufo\DTO\Interfaces\DTOFromArrayTransformerInterface;
use Ufo\DTO\Interfaces\IDTOFromArrayTransformer;
use Ufo\DTO\Transformer\Converter\EnumConverter;
use function class_implements;
use function class_parents;

enum DTOAttributesEnum: string
{
    case ASSERTIONS = AttrAssertions::class;
    case DTO = AttrDTO::class;

    /**
     * @psalm-param class-string<IDTOFromArrayTransformer>|DTOFromArrayTransformerInterface $dtoTransformer
     */
    public static function tryFromAttr(
        ReflectionAttribute $attributeDefinition,
        mixed $value,
        ReflectionProperty|ReflectionParameter $property,
        string|DTOFromArrayTransformerInterface $dtoTransformer,
    ): mixed
    {
        $attribute = $attributeDefinition->newInstance();
        try {
            return self::from($attributeDefinition->name)->process($attribute, $value, $property, $dtoTransformer);
        } catch (\ValueError) {
            foreach (class_parents($attribute) as $parentAttribute) {
                try {
                    return self::from($parentAttribute)->process($attribute, $value, $property, $dtoTransformer);
                } catch (\ValueError) {}
            }
            throw new \ValueError('Unsupported attribute type');
        }
    }

    /**
     * @psalm-param class-string<IDTOFromArrayTransformer>|DTOFromArrayTransformerInterface $dtoTransformer
     */
    public function process(
        object $attribute,
        mixed $value,
        ReflectionProperty|ReflectionParameter $property,
        string|DTOFromArrayTransformerInterface $dtoTransformer,
    ): mixed
    {
        return match ($this) {
            self::ASSERTIONS => $this->validate($attribute, $value, $property),
            self::DTO => $this->resolveDTO($attribute, $value, $dtoTransformer),
        };
    }

    /**
     * @psalm-param class-string<IDTOFromArrayTransformer>|DTOFromArrayTransformerInterface $dtoTransformer
     */
    protected function resolveDTO(
        AttrDTO $attribute,
        mixed $value,
        string|DTOFromArrayTransformerInterface $dtoTransformer,
    ): array|object
    {
        if ($attribute->isCollection()) {
            return $this->transformDTOCollection($attribute, $value, $dtoTransformer);
        }
        return $this->transformDto($attribute, $value, $dtoTransformer);
    }

    /**
     * @psalm-param class-string<IDTOFromArrayTransformer>|DTOFromArrayTransformerInterface $dtoTransformer
     */
    protected function transformDTOCollection(
        AttrDTO $attribute,
        mixed $value,
        string|DTOFromArrayTransformerInterface $dtoTransformer,
    ): array
    {
        $result = [];
        foreach ($value as $key => $item) {
            try {
                $result[$key] = $this->transformDto($attribute, $item, $dtoTransformer);
            } catch (Throwable $e) {
                if ($attribute->isStrict()) throw $e;
                $result[$key] = $item;
            }
        }
        return $result;
    }

    /**
     * @psalm-param class-string<IDTOFromArrayTransformer>|DTOFromArrayTransformerInterface $dtoTransformer
     * @throws BadParamException
     * @throws NotSupportDTOException
     */
    protected function transformDto(
        AttrDTO $attribute,
        mixed $value,
        string|DTOFromArrayTransformerInterface $dtoTransformer,
    ): object
    {
        if ($attribute->isEnum()) {
            return EnumConverter::toEnum($attribute->dtoFQCN, $value);
        }

        if ($customDTOTransformerFQCN = $attribute->transformerFQCN()) {
            $implements = class_implements($customDTOTransformerFQCN);
            if ($implements[IDTOFromArrayTransformer::class] ?? false) {
                /**
                 * @var IDTOFromArrayTransformer $customDTOTransformerFQCN
                 */
                if (!$customDTOTransformerFQCN::isSupportClass($attribute->dtoFQCN)) {
                    throw new NotSupportDTOException($this->transformerName($dtoTransformer) . ' is not support transform for ' . $attribute->dtoFQCN);
                }
                return $customDTOTransformerFQCN::fromArray($attribute->dtoFQCN, $value, $attribute->renameKeys());
            }
        }

        if (is_string($dtoTransformer)) {
            return $dtoTransformer::fromArray($attribute->dtoFQCN, $value, $attribute->renameKeys(), $attribute->namespaces());
        }

        return $dtoTransformer->transformFromArray($attribute->dtoFQCN, $value, $attribute->renameKeys(), $attribute->namespaces());
    }

    protected function transformerName(string|DTOFromArrayTransformerInterface $dtoTransformer): string
    {
        return is_string($dtoTransformer) ? $dtoTransformer : $dtoTransformer::class;
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
