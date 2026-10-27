<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Fixtures;

use Ufo\DTO\Transformer\Hydrator\AbstractParamHydrator;
use Ufo\DTO\VO\TransformationContext;

final class LegacyUpperHydrator extends AbstractParamHydrator
{
    public function supports(array $schema): bool
    {
        return ($schema['format'] ?? null) === 'upper';
    }

    public function resolve(array $schema, mixed $value, TransformationContext $context): mixed
    {
        return strtoupper((string) $value);
    }
}
