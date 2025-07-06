<?php

namespace Ufo\DTO\Tests\Fixtures;


use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;

class ApiMethod
{
    /**
     * @return DummyDTO[]
     */
    public function getMore(): array
    {
        return [
            new DummyDTO(1, 'qwe'),
        ];
    }

    public function getOne(): DummyDTO
    {
        return new DummyDTO(1, 'qwe');
    }
}