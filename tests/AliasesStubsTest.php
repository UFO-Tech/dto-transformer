<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests;

use PHPUnit\Framework\TestCase;

use const Ufo\DTO\DEPRECATED_ALIASES;

final class AliasesStubsTest extends TestCase
{
    public function testStubsDeclareEveryDeprecatedNameExtendingItsReplacement(): void
    {
        $expected = DEPRECATED_ALIASES;
        ksort($expected);

        $declared = self::parseStubs(dirname(__DIR__) . '/aliases-stubs.php');
        ksort($declared);

        self::assertSame($expected, $declared);
    }

    public function testDeprecatedNamesResolveToReplacements(): void
    {
        foreach (DEPRECATED_ALIASES as $deprecated => $replacement) {
            self::assertSame($replacement, (new \ReflectionClass($deprecated))->getName());
        }
    }

    /** @return array<string, string> */
    private static function parseStubs(string $file): array
    {
        $tokens = array_values(array_filter(
            token_get_all(file_get_contents($file)),
            static fn (array|string $token): bool => !is_array($token) || !in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true),
        ));

        $declared = [];
        $namespace = '';
        foreach ($tokens as $i => $token) {
            if (!is_array($token)) {
                continue;
            }
            if ($token[0] === T_NAMESPACE) {
                $namespace = $tokens[$i + 1][1];
                continue;
            }
            if ($token[0] === T_STRING && $token[1] === 'class_alias') {
                $declared[trim($tokens[$i + 4][1], '\'')] = trim($tokens[$i + 2][1], '\'');
                continue;
            }
            if (!in_array($token[0], [T_CLASS, T_INTERFACE, T_TRAIT], true)) {
                continue;
            }

            $name = $namespace . '\\' . $tokens[$i + 1][1];
            $parentAt = $token[0] === T_TRAIT ? $i + 4 : $i + 3;
            $declared[$name] = ltrim($tokens[$parentAt][1], '\\');
        }

        return $declared;
    }
}
