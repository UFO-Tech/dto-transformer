<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Support;

use Ufo\DTO\Transformer\Context\TransformationContext;
use Ufo\DTO\Transformer\DTOFromArrayTransformer;

final class RecordingDTOFromArrayTransformer extends DTOFromArrayTransformer
{
    /**
     * @var list<string>
     */
    public array $hydrated = [];

    protected function hydrateClass(
        string $classFQCN,
        array $data,
        array $renameKey,
        TransformationContext $context,
    ): object
    {
        $this->hydrated[] = $classFQCN;

        return parent::hydrateClass($classFQCN, $data, $renameKey, $context);
    }
}
