<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Hydrator;

use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Exceptions\NotSupportDTOException;
use Ufo\DTO\Exceptions\UnionTypeMismatchException;
use Ufo\DTO\Schema\SchemaNode;
use Ufo\DTO\Schema\Type\TypeHintResolver;
use Ufo\DTO\Transformer\Context\TransformationContext;

use function array_values;
use function is_array;
use function is_float;
use function is_int;
use function is_string;
use function ksort;

class UnionHydrator extends AbstractHydrator implements SchemaNodeHydratorInterface
{
    public function supports(array $schema): bool
    {
        return isset($schema[TypeHintResolver::ONE_OFF]);
    }

    public function resolve(
        array $schema,
        mixed $value,
        TransformationContext $context,
    ): mixed
    {
        if ($this->chainHydrator instanceof HydratorChain) {
            return $this->resolveNode($this->chainHydrator->node($schema), $value, $context);
        }

        if ($value === null && $this->allowsNull($schema)) {
            return null;
        }

        $branchContext = $context->withStrict(true);
        $errors = [];
        foreach ($schema[TypeHintResolver::ONE_OFF] as $subSchema) {
            try {
                return $this->resolveNested($subSchema, $value, $branchContext);
            } catch (NotSupportDTOException|BadParamException $exception) {
                $errors[] = $exception;
            }
        }

        if ($context->isStrict()) {
            throw new UnionTypeMismatchException($errors);
        }

        return $value;
    }

    public function resolveNode(
        SchemaNode $node,
        mixed $value,
        TransformationContext $context,
    ): mixed
    {
        if ($value === null && $node->allowsNull) {
            return null;
        }

        $branchContext = $context->withStrict(true);
        $errors = [];
        $rejected = [];
        foreach ($node->branches as $index => $branch) {
            if ($this->branchRejects($branch->hydrator, $branch->schema, $value)) {
                $rejected[] = $index;
                continue;
            }

            try {
                return $branch->resolve($value, $branchContext);
            } catch (NotSupportDTOException|BadParamException $exception) {
                $errors[$index] = $exception;
            }
        }

        if (!$context->isStrict()) {
            return $value;
        }

        foreach ($rejected as $index) {
            try {
                $node->branches[$index]->resolve($value, $branchContext);
            } catch (NotSupportDTOException|BadParamException $exception) {
                $errors[$index] = $exception;
            }
        }
        ksort($errors);

        throw new UnionTypeMismatchException(array_values($errors));
    }

    protected function allowsNull(array $schema): bool
    {
        foreach ($schema[TypeHintResolver::ONE_OFF] as $subSchema) {
            if (($subSchema[TypeHintResolver::TYPE] ?? null) === TypeHintResolver::NULL->value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Гілка гарантовано кине перехоплюваний виняток: її гідратор відомий і не приймає такий тип значення.
     */
    protected function branchRejects(?HydratorInterface $hydrator, array $subSchema, mixed $value): bool
    {
        return match ($hydrator === null ? null : $hydrator::class) {
            ScalarHydrator::class => !(
                TypeHintResolver::tryFrom(TypeHintResolver::jsonSchemaToPhp($subSchema[TypeHintResolver::TYPE]))?->matchType($value)
                ?? false
            ),
            EnumHydrator::class =>!is_string($value) && !is_int($value),
            DateTimeHydrator::class => !is_int($value) && !is_string($value) && !is_float($value) && $value !== null,
            DTOHydrator::class =>$hydrator->rejects($subSchema, $value),
            ArrayItemsHydrator::class, AdditionalHydrator::class => !is_array($value),
            default => false,
        };
    }
}
