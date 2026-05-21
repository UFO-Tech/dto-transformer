<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Hydrator;

use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Exceptions\NotSupportDTOException;
use Ufo\DTO\Exceptions\UnionTypeMismatchException;
use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\VO\TransformationContext;

class UnionParamHydrator extends AbstractParamHydrator
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
        $errors = [];
        foreach ($schema[TypeHintResolver::ONE_OFF] as $subSchema) {
            try {
                return $this->resolveNested(
                    $subSchema,
                    $value,
                    $context->withStrict(true),
                );
            } catch (NotSupportDTOException|BadParamException $exception) {
                $errors[] = $exception;
            }
        }

        if ($context->isStrict()) {
            throw new UnionTypeMismatchException($errors);
        }

        return $value;
    }
}
