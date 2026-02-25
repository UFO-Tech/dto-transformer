<?php

namespace Ufo\DTO\Tests;

use PHPUnit\Framework\TestCase;
use Ufo\DTO\DTOTransformer;
use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Exceptions\NotSupportDTOException;
use Ufo\DTO\Tests\Fixtures\DTO\UserDto;

use function time;

class ArrayConstructibleTraitTest extends TestCase
{
    /**
     * Test that fromArray creates a valid DTO instance with provided data.
     */
    public function testFromArrayCreatesValidInstance()
    {
        $data = ['name' => 'John Doe', 'email' => 'john.doe@example.com', 'currentTime' => time()];
        $userDto = UserDto::fromArray($data);

        $this->assertInstanceOf(UserDto::class, $userDto);
        $this->assertSame('John Doe', $userDto->name);
        $this->assertSame('john.doe@example.com', $userDto->email);
    }

    /**
     * Test that fromArray throws BadParamException for invalid data.
     */
    public function testFromArrayThrowsBadParamException()
    {
        $this->expectException(BadParamException::class);

        $data = ['invalid' => 'data'];
        UserDto::fromArray($data);
    }

    /**
     * Test that fromArray handles renamed keys correctly.
     */
    public function testFromArrayWithRenamedKeys()
    {
        $data = ['full_name' => 'John Doe', 'contact_email' => 'john.doe@example.com', 'currentTime' => time()];
        $renameKey = ['name' => 'full_name', 'email' => 'contact_email'];

        $userDto = UserDto::fromArray($data, $renameKey);

        $this->assertSame('John Doe', $userDto->name);
        $this->assertSame('john.doe@example.com', $userDto->email);
    }
}