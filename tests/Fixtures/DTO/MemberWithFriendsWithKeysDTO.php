<?php

namespace Ufo\DTO\Tests\Fixtures\DTO;

use Ufo\DTO\Attributes\AttrDTO;

class MemberWithFriendsWithKeysDTO
{
    public function __construct(
        public DummyDTO $user,
        #[AttrDTO(DummyDTO::class, context: [
            AttrDTO::C_COLLECTION => true
        ])]
        public array $friends
    ) {}
}