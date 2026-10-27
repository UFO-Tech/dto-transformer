<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Hydrator;

use Throwable;
use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Exceptions\InvalidObjectValueException;
use Ufo\DTO\Schema\Type\TypeHintResolver;
use Ufo\DTO\Transformer\Context\TransformationContext;
use Ufo\DTO\Transformer\DTOCandidateFilterInterface;
use Ufo\DTO\Transformer\DTOFromArrayTransformerInterface;
use Ufo\DTO\Transformer\DTOFromContextTransformerInterface;
use function get_debug_type;
use function sprintf;

class DTOHydrator implements HydratorInterface
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

        if ($value instanceof $classFQCN) {
            return $value;
        }

        if (!is_array($value)) {
            throw new InvalidObjectValueException(sprintf(
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

    /**
     * true лише тоді, коли resolve() гарантовано кине BadParamException для цього значення.
     */
    public function rejects(array $schema, mixed $value): bool
    {
        $classFQCN = $schema[TypeHintResolver::CLASS_FQCN];

        if (!is_array($value)) {
            return !($value instanceof $classFQCN);
        }

        foreach ($this->fromArrayTransformers as $transformer) {
            if ($transformer->support($classFQCN)) return false;
        }

        return $this->defaultDTOTransformer instanceof DTOCandidateFilterInterface
            && $this->defaultDTOTransformer->rejectsData($classFQCN, $value);
    }

    protected function transformWith(
        DTOFromArrayTransformerInterface $transformer,
        string $classFQCN,
        array $value,
        TransformationContext $context,
    ): object
    {
        try {
            if ($transformer instanceof DTOFromContextTransformerInterface) {
                return $transformer->transformFromContext($classFQCN, $value, $context);
            }

            return $transformer->transformFromArray($classFQCN, $value, namespaces: $context->namespaces(), context: $context->toArray());
        } catch (BadParamException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new BadParamException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
