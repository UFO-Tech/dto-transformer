<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Fixtures\DTO;

use Symfony\Component\Serializer\Attribute\Ignore;

final class IgnoredRequiredDTO
{
    #[Ignore]
    public string $secret;

    public function __construct(
        public int $id,
    ) {}
}
