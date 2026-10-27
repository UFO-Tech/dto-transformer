<?php

declare(strict_types=1);

namespace Ufo\DTO\Schema;

use Ufo\DTO\BaseDTOFromArrayTransformer;
use Ufo\DTO\Transformer\DTOCandidateFilterInterface;

use function array_key_exists;

/**
 * Відсіює кандидата union, якщо в даних немає ключа властивості без значення за замовчуванням.
 */
class RequiredKeysCandidateFilter implements DTOCandidateFilterInterface
{
    public function __construct(
        protected ClassSchemaProviderInterface $schemaProvider,
    ) {}

    public function rejectsData(string $classFQCN, array $data, array $renameKey = []): bool
    {
        if ($renameKey !== [] || isset($data[BaseDTOFromArrayTransformer::DTO_CLASSNAME])) {
            return false;
        }

        try {
            $requiredKeys = $this->schemaProvider->schema($classFQCN)->requiredKeys;
        } catch (\Throwable) {
            return false;
        }

        return array_any($requiredKeys, fn ($key) => !array_key_exists($key, $data));
    }
}
