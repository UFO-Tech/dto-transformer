<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Hydrator\Cache;

use Ufo\DTO\Helpers\BoundedArray;
use Ufo\DTO\Schema\SchemaNode;
use Ufo\DTO\Transformer\Hydrator\HydratorChain;
use Ufo\DTO\Transformer\Hydrator\HydratorInterface;

use function json_encode;
use function md5;

/**
 * Ланцюг гідраторів зі скомпільованими вузлами в пам'яті процесу; дочірні вузли кешуються теж.
 */
class CachedHydratorChain extends HydratorChain
{
    /** Максимум схем у кеші скомпільованих вузлів; схема залежить від namespaces викликача. */
    protected const int HYDRATOR_CACHE_LIMIT = 4096;

    /**
     * @var array<string, SchemaNode>
     */
    protected array $nodeBySchema = [];

    public function addHydrator(HydratorInterface $hydrator): self
    {
        $this->nodeBySchema = [];

        return parent::addHydrator($hydrator);
    }

    public function node(array $schema): SchemaNode
    {
        $schemaKey = $this->schemaKey($schema);

        return $this->nodeBySchema[$schemaKey]
            ?? BoundedArray::put($this->nodeBySchema, $schemaKey, $this->compile($schema), static::HYDRATOR_CACHE_LIMIT);
    }

    protected function schemaKey(array $schema): string
    {
        return md5(json_encode($schema));
    }
}
