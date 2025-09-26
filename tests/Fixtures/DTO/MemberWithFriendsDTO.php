<?php

namespace Ufo\DTO\Tests\Fixtures\DTO;

use Ufo\DTO\Attributes\AttrDTO;

class MemberWithFriendsDTO
{
    public function __construct(
        public UserDto $user,
        #[AttrDTO(UserDto::class, context: [
            AttrDTO::C_COLLECTION => true,
            AttrDTO::C_RENAME_KEYS => ['currentTime' => null],
        ])]
        public array $friends
    ) {}
}