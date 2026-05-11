<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Hydrator;

use Throwable;
use Ufo\DTO\BaseDTOFromArrayTransformer;
use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\Interfaces\DTOFromArrayTransformerInterface;
use Ufo\DTO\Interfaces\Hydrator\ParamHydratorInterface;
use Ufo\DTO\VO\TransformationContext;
use function get_debug_type;
use function is_array;
use function sprintf;

class DtoHydrator implements ParamHydratorInterface
{
    /**
     * @param iterable<DTOFromArrayTransformerInterface> $fromArrayTransformers
     */
    public function __construct(
        protected DTOFromArrayTransformerInterface $defaultDTOTransformer,
        protected iterable $fromArrayTransformers = [],
    ) {
        foreach ($fromArrayTransformers as $transformer) {
            if (!$transformer instanceof DTOFromArrayTransformerInterface) {
                throw new \InvalidArgumentException(sprintf(
                   '%s must implement %s',
                   $transformer::class,
                   DTOFromArrayTransformerInterface::class,
                ));
            }
        }
    }

    public function supports(array $schema): bool
    {
        return isset($schema[TypeHintResolver::CLASS_FQCN]);
    }

    public function resolve(
        array $schema,
        mixed $value,
        TransformationContext $context,
    ): object
    {
        $classFQCN = $schema[TypeHintResolver::CLASS_FQCN];

        if (is_array($value) && isset($value[BaseDTOFromArrayTransformer::DTO_CLASSNAME])) {
            $classFQCN = $this->resolveSmartClass($value[BaseDTOFromArrayTransformer::DTO_CLASSNAME], $context);
            unset($value[BaseDTOFromArrayTransformer::DTO_CLASSNAME]);
        }

        if ($value instanceof $classFQCN) {
            return $value;
        }

        if (!is_array($value)) {
            throw new BadParamException(sprintf(
                'Cannot assign %s to DTO %s',
                get_debug_type($value),
                $classFQCN,
            ));
        }

        foreach ($this->fromArrayTransformers as $transformer) {
            if (!$transformer->support($classFQCN)) continue;

            return $this->transformWith($transformer, $classFQCN, $value, $context);
        }

        return $this->transformWith($this->defaultDTOTransformer, $classFQCN, $value, $context);
    }

    protected function resolveSmartClass(mixed $classFQCN, TransformationContext $context): string
    {
        if (!is_string($classFQCN)) {
            throw new BadParamException('Smart DTO class name must be a string');
        }

        if (class_exists($classFQCN)) {
            return $classFQCN;
        }

        return TypeHintResolver::typeWithNamespaceOrDefault(
            $classFQCN,
            $context->namespaces,
            BaseDTOFromArrayTransformer::DTO_NS_KEY,
        ) ?? throw new BadParamException('Namespace not found for class: ' . $classFQCN);
    }

    protected function transformWith(
        DTOFromArrayTransformerInterface $transformer,
        string $classFQCN,
        array $value,
        TransformationContext $context,
    ): object {
        try {
            return $transformer->transformFromArray($classFQCN, $value, namespaces: $context->namespaces);
        } catch (BadParamException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new BadParamException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
