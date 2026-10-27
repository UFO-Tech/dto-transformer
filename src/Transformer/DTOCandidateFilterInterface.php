<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer;

/**
 * Дешевий відсів класів-кандидатів union без гідрації.
 */
interface DTOCandidateFilterInterface
{
    /**
     * true лише тоді, коли гідрація класу з цими даними гарантовано впаде.
     */
    public function rejectsData(string $classFQCN, array $data, array $renameKey = []): bool;
}
