<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks\Fixture;

use RuntimeException;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;
use Ufo\DTO\Tests\Fixtures\DTO\MemberWithFriendsDTO;

final class FixtureAssertions
{
    private const array ROOT_KEYS = ['dummyItems', 'users', 'members', 'enumBackedItems', 'arrayObjects'];

    public static function rootDtos(mixed $result, int $count, int $nestedSize = BenchmarkPayloadFactory::NESTED_SIZE): void
    {
        $last = is_array($result) && count($result) === $count ? end($result) : null;
        $dummy = $last?->dummyItems[$nestedSize - 1] ?? null;
        $member = $last?->members[0] ?? null;

        if (
            !$dummy instanceof DummyDTO
            || $dummy->id !== $nestedSize - 1
            || count($last->users) !== $nestedSize
            || !$member instanceof MemberWithFriendsDTO
            || count($member->friends) !== 3
        ) {
            throw new RuntimeException(sprintf('Unexpected fromArray result for %d root DTO of nested size %d', $count, $nestedSize));
        }
    }

    public static function rootArrays(mixed $result, int $count, int $nestedSize = BenchmarkPayloadFactory::NESTED_SIZE): void
    {
        $last = is_array($result) && count($result) === $count ? end($result) : null;

        if (
            !is_array($last)
            || array_keys($last) !== self::ROOT_KEYS
            || ($last['dummyItems'][$nestedSize - 1]['id'] ?? null) !== $nestedSize - 1
            || count($last['users']) !== $nestedSize
            || count($last['members'][0]['friends'] ?? []) !== 3
        ) {
            throw new RuntimeException(sprintf('Unexpected toArray result for %d root DTO of nested size %d', $count, $nestedSize));
        }
    }

    /**
     * @param list<class-string> $classes
     */
    public static function generatedDtos(mixed $result, array $classes): void
    {
        $lastIndex = count($classes) - 1;
        $last = is_array($result) && count($result) === count($classes) ? end($result) : null;

        if (!$last instanceof $classes[$lastIndex] || $last->p1 !== $lastIndex) {
            throw new RuntimeException(sprintf('Unexpected fromArray result for %d generated classes', count($classes)));
        }
    }

    public static function generatedArrays(mixed $result, int $count): void
    {
        $last = is_array($result) && count($result) === $count ? end($result) : null;

        if ($last !== GeneratedClasses::payload($count - 1)) {
            throw new RuntimeException(sprintf('Unexpected toArray result for %d generated classes', $count));
        }
    }
}
