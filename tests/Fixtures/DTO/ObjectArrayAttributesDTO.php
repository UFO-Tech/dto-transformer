<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Fixtures\DTO;

use Ufo\DTO\Attributes\StrictMode;

class ObjectArrayAttributesDTO
{
    /**
     * @var object[]
     */
    #[StrictMode]
    protected array $strictAttributes = [];

    /**
     * @var object[]
     */
    protected array $attributes = [];

    public function strictAttributes(): array
    {
        return $this->strictAttributes;
    }

    public function attributes(): array
    {
        return $this->attributes;
    }
}
