<?php

namespace Ufo\DTO\Tests\Fixtures\DTO;

use Ufo\DTO\Attributes\AttrDTO;

class MemberWithFriendsDTO
{
    public function __construct(
        public UserDto $user,
        #[AttrDTO(UserDto::class, collection: true, renameKeys: ['currentTime' => null])]
        public array $friends
    ) {}
}