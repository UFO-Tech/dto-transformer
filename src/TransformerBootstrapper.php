<?php

declare(strict_types = 1);

namespace Ufo\DTO;

use Ufo\DTO\Factory\DefaultDTOTransformerFactory;

final class TransformerBootstrapper
{
    private function __construct() {}

    private function __clone() {}

    public function __wakeup(): void
    {
        throw new \Error('Cannot unserialize singleton');
    }

    public function __unserialize(array $data): void
    {
        throw new \Error('Cannot unserialize singleton');
    }

    public static function boot(): void
    {
        $factory = DefaultDTOTransformerFactory::default();

        // boot() throws if already initialised — safe to guard so the file can be required twice.
        if (!DTOTransformer::isInitialized()) {
            DTOTransformer::boot($factory->create(DTOTransformer::class));
        }
        if (!ServiceTransformer::isInitialized()) {
            ServiceTransformer::boot($factory->create(ServiceTransformer::class));
        }
    }
}