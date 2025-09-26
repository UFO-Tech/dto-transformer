<?php

namespace Ufo\DTO\Tests;

use PHPUnit\Framework\TestCase;
use Ufo\DTO\StringTransformerEnum;

class StringTransformerEnumTest extends TestCase
{
    /**
     * Tests the transliterate method with a basic string input.
     */
    public function testTransliterateWithBasicString(): void
    {
        $input = "HelloWorld123";
        $expected = "HelloWorld123";
        $result = StringTransformerEnum::transliterate($input);

        $this->assertSame($expected, $result);
    }

    /**
     * Tests the transformName method with a camelCase input.
     */
    public function testTransformNameWithCamelCase(): void
    {
        $input = "camelCaseInput";
        $expected = "CAMEL_CASE_INPUT";
        $result = StringTransformerEnum::transformName($input);

        $this->assertSame($expected, $result);
    }

    /**
     * Tests the transformName method with a string starting with a digit.
     */
    public function testTransformNameWithLeadingDigit(): void
    {
        $input = "1stPlace";
        $expected = "CASE_1ST_PLACE";
        $result = StringTransformerEnum::transformName($input);

        $this->assertSame($expected, $result);
    }

    /**
     * Tests the transformName method with an entirely uppercase input.
     */
    public function testTransformNameWithUpperCase(): void
    {
        $input = "UPPERCASE";
        $expected = "UPPERCASE";
        $result = StringTransformerEnum::transformName($input);

        $this->assertSame($expected, $result);

        $input2 = "UPPER_CASE";
        $expected2 = "UPPER_CASE";
        $result2 = StringTransformerEnum::transformName($input2);
        $this->assertSame($expected2, $result2);
    }

    /**
     * Tests the transformName method with special characters in input.
     */
    public function testTransformNameWithSpecialCharacters(): void
    {
        $input = "@Sp3ci@l_Chars!";
        $expected = "SP3CIL_CHARS";
        $result = StringTransformerEnum::transformName($input);

        $this->assertSame($expected, $result);
    }

    /**
     * Tests the transformName method with an empty string.
     */
    public function testTransformNameWithEmptyString(): void
    {
        $input = "";
        $expected = "EMPTY";
        $result = StringTransformerEnum::transformName($input);

        $this->assertSame($expected, $result);
    }

    /**
     * Tests the transliterate method with special characters and non-ASCII input.
     */
    public function testTransliterateWithSpecialCharacters(): void
    {
        $input = "Hèllô Wørld! Fün123";
        $expected = "HelloWorldFun123";
        $result = StringTransformerEnum::transliterate($input);

        $this->assertSame($expected, $result);
    }

    /**
     * Tests the transliterate method with entirely non-ASCII input.
     */
    public function testTransliterateWithNonAsciiInput(): void
    {
        $input = "Привет Мир!";
        $expected = "PrivetMir";
        $result = StringTransformerEnum::transliterate($input);

        $this->assertSame($expected, $result);
    }

    /**
     * Tests the transliterate method with symbols only.
     */
    public function testTransliterateWithSymbolsOnly(): void
    {
        $input = "!@#$%^&*()_+";
        $expected = "_";
        $result = StringTransformerEnum::transliterate($input);

        $this->assertSame($expected, $result);
    }

    /**
     * Tests the transliterate method with an empty string.
     */
    public function testTransliterateWithEmptyString(): void
    {
        $input = "";
        $expected = "";
        $result = StringTransformerEnum::transliterate($input);

        $this->assertSame($expected, $result);
    }
}