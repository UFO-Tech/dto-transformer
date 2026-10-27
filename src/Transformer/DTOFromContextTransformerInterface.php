<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer;

use Ufo\DTO\Transformer\Context\TransformationContext;

/**
 * Гідрація вкладеного DTO з готовим контекстом, без розгортання його в масив.
 */
interface DTOFromContextTransformerInterface
{
    public function transformFromContext(
        string $classFQCN,
        array $data,
        TransformationContext $context,
        array $renameKey = [],
    ): object;
}
