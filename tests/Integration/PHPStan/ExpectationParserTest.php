<?php

/**
 * +--------------------------------------------------------------------------------------------------------------+
 * |        *                 .                         *                  .                         *            |
 * |   .              *                      .                    *                      .                        |
 * |             .                 .                  *                         .                 *               |
 * -      *                    .             *                    .                         .                     -
 *
 *                               Probatio Verborum Viventium『証』〜ＡＫＡＳＨＩ〜
 *
 * -                                          .----------------.                                                  -
 * |                                      .--'        __        '--.                                              |
 * |                                  .--'          .'  '.          '--.                                          |
 * |                             .---'            .'      '.            '---.                                     |
 * +--------------------------------------------------------------------------------------------------------------+
 *
 * Copyright (c) anno Domini nostri Jesu Christi MMXXVI, John Boehr & contributors
 *
 * SPDX-License-Identifier: AGPL-3.0-only WITH romic-exception
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License version 3,
 * as published by the Free Software Foundation, together with the Romic
 * Exception (an additional permission under section 7 of that license).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * and the Romic Exception along with this program.  If not, see
 * <http://www.gnu.org/licenses/> and the LICENSE_EXCEPTION file.
 */

declare(strict_types=1);

namespace jbboehr\Akashi\Tests\Integration\PHPStan;

use jbboehr\Akashi\Document;
use jbboehr\Akashi\Example;
use jbboehr\Akashi\Integration\PHPStan\Exception\ExpectationParseException;
use jbboehr\Akashi\Integration\PHPStan\ExpectationParser;
use jbboehr\Akashi\Model\ExampleCode;
use jbboehr\Akashi\Model\ExampleId;
use jbboehr\Akashi\Model\FenceMetadata;
use jbboehr\Akashi\Model\Language;
use jbboehr\Akashi\Model\SourceLocation;
use jbboehr\Akashi\Model\SourceSpan;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ExpectationParserTest extends TestCase
{
    public function testParsesExpectationsInAuthoredOrderWithMaintainedLines(): void
    {
        $expectations = (new ExpectationParser())->parse($this->example(
            "<?php\n//! first diagnostic\necho 'work';\n  //!second diagnostic  \n",
        ));

        self::assertCount(2, $expectations);
        self::assertSame('first diagnostic', $expectations[0]->text);
        self::assertNull($expectations[0]->identifier);
        self::assertSame(21, $expectations[0]->sourceLine);
        self::assertSame('second diagnostic', $expectations[1]->text);
        self::assertNull($expectations[1]->identifier);
        self::assertSame(23, $expectations[1]->sourceLine);
    }

    public function testParsesIdentifierOnlyAndCombinedExpectations(): void
    {
        $expectations = (new ExpectationParser())->parse($this->example(
            <<<'PHP'
// @akashi-phpstan-error argument.type
$value = invalid();
// @akashi-phpstan-error method.notFound: undefined method
$service->missing();
PHP,
        ));

        self::assertCount(2, $expectations);
        self::assertNull($expectations[0]->text);
        self::assertSame('argument.type', $expectations[0]->identifier);
        self::assertSame(20, $expectations[0]->sourceLine);
        self::assertSame(['first' => 21, 'last' => 21], $expectations[0]->sourceLineRange);
        self::assertSame('undefined method', $expectations[1]->text);
        self::assertSame('method.notFound', $expectations[1]->identifier);
        self::assertSame(22, $expectations[1]->sourceLine);
        self::assertSame(['first' => 23, 'last' => 23], $expectations[1]->sourceLineRange);
    }

    public function testKeepsOtherBracketedTextAsALegacySubstringExpectation(): void
    {
        $expectations = (new ExpectationParser())->parse($this->example('//! [argument.type] legacy text'));

        self::assertSame('[argument.type] legacy text', $expectations[0]->text);
        self::assertNull($expectations[0]->identifier);
    }

    #[DataProvider('lineEndingProvider')]
    public function testAcceptsAllCommonLineEndings(string $lineEnding): void
    {
        $source = '//! first' . $lineEnding . 'echo 1;' . $lineEnding . '//! second';

        $expectations = (new ExpectationParser())->parse($this->example($source));

        self::assertSame(['first', 'second'], array_column($expectations, 'text'));
        self::assertSame([20, 22], array_column($expectations, 'sourceLine'));
    }

    /** @return iterable<string, array{string}> */
    public static function lineEndingProvider(): iterable
    {
        yield 'LF' => ["\n"];
        yield 'CRLF' => ["\r\n"];
        yield 'CR' => ["\r"];
    }

    public function testIgnoresMarkerTextOutsideAStandaloneLineComment(): void
    {
        $source = "echo '//! string';\n// ordinary //! comment\n/* //! block comment */";

        self::assertSame([], (new ExpectationParser())->parse($this->example($source)));
    }

    public function testReturnsAnEmptyListForACleanExample(): void
    {
        self::assertSame([], (new ExpectationParser())->parse($this->example('echo 1;')));
    }

    #[DataProvider('emptyExpectationProvider')]
    public function testRejectsAnEmptyExpectation(string $marker): void
    {
        $this->expectException(ExpectationParseException::class);
        $this->expectExceptionMessage(
            'Example phpstan-example-01 at docs/phpstan.md:21 contains an empty PHPStan diagnostic expectation.',
        );

        (new ExpectationParser())->parse($this->example("echo 1;\n" . $marker));
    }

    /** @return iterable<string, array{string}> */
    public static function emptyExpectationProvider(): iterable
    {
        yield 'no suffix' => ['//!'];
        yield 'space' => ['//! '];
        yield 'indented horizontal whitespace' => [" \t//!\t"];
    }

    #[DataProvider('malformedIdentifierExpectationProvider')]
    public function testRejectsAMalformedIdentifierExpectation(string $marker): void
    {
        $this->expectException(ExpectationParseException::class);
        $this->expectExceptionMessage(
            'Example phpstan-example-01 at docs/phpstan.md:20 contains a malformed PHPStan diagnostic identifier expectation.',
        );

        (new ExpectationParser())->parse($this->example($marker));
    }

    /** @return iterable<string, array{string}> */
    public static function malformedIdentifierExpectationProvider(): iterable
    {
        yield 'missing identifier' => ["// @akashi-phpstan-error\n\$value = 1;"];
        yield 'empty message' => ["// @akashi-phpstan-error argument.type:\n\$value = 1;"];
        yield 'whitespace message' => ["// @akashi-phpstan-error argument.type:   \n\$value = 1;"];
        yield 'identifier containing whitespace' => ["// @akashi-phpstan-error argument type\n\$value = 1;"];
    }

    public function testRejectsAnIdentifierExpectationWithoutAFollowingStatement(): void
    {
        $this->expectException(ExpectationParseException::class);
        $this->expectExceptionMessage(
            'Example phpstan-example-01 at docs/phpstan.md:20 contains a PHPStan diagnostic identifier expectation that is not followed by a statement.',
        );

        (new ExpectationParser())->parse($this->example('// @akashi-phpstan-error argument.type'));
    }

    public function testRejectsInterveningContentBeforeTheAssociatedStatement(): void
    {
        $this->expectException(ExpectationParseException::class);
        $this->expectExceptionMessage('does not immediately precede a statement');

        (new ExpectationParser())->parse($this->example(
            <<<'PHP'
// @akashi-phpstan-error argument.type
// ordinary comment
$value = invalid();
PHP,
        ));
    }

    public function testAssociatesRepeatedExpectationsWithTheNextMultilineStatement(): void
    {
        $expectations = (new ExpectationParser())->parse($this->example(
            "// @akashi-phpstan-error argument.type\n"
            . "// @akashi-phpstan-error argument.templateType\n \t \n"
            . "accepts(\n"
            . "    invalid(),\n"
            . ");",
        ));

        self::assertCount(2, $expectations);
        self::assertSame(['argument.type', 'argument.templateType'], array_column($expectations, 'identifier'));
        self::assertSame(['first' => 23, 'last' => 25], $expectations[0]->sourceLineRange);
        self::assertSame(['first' => 23, 'last' => 25], $expectations[1]->sourceLineRange);
    }

    public function testRejectsANonStandaloneIdentifierExpectation(): void
    {
        $this->expectException(ExpectationParseException::class);
        $this->expectExceptionMessage(
            'Example phpstan-example-01 at docs/phpstan.md:20 contains a misplaced PHPStan diagnostic identifier '
            . 'expectation; the directive must occupy a standalone line.',
        );

        (new ExpectationParser())->parse($this->example(
            '$value = invalid(); // @akashi-phpstan-error argument.type',
        ));
    }

    public function testRejectsAnIdentifierExpectationInsideAnArgumentList(): void
    {
        $this->expectException(ExpectationParseException::class);
        $this->expectExceptionMessage('not followed by a statement');

        (new ExpectationParser())->parse($this->example(
            <<<'PHP'
accepts(
    // @akashi-phpstan-error argument.type
    invalid(),
);
PHP,
        ));
    }

    public function testParsesLegacyAndIdentifierExpectationsTogether(): void
    {
        $expectations = (new ExpectationParser())->parse($this->example(
            <<<'PHP'
//! mutable diagnostic text
// @akashi-phpstan-error argument.type
$value = invalid();
PHP,
        ));

        self::assertCount(2, $expectations);
        self::assertSame('mutable diagnostic text', $expectations[0]->text);
        self::assertNull($expectations[0]->identifier);
        self::assertNull($expectations[0]->sourceLineRange);
        self::assertNull($expectations[1]->text);
        self::assertSame('argument.type', $expectations[1]->identifier);
        self::assertSame(['first' => 22, 'last' => 22], $expectations[1]->sourceLineRange);
    }

    #[DataProvider('lineEndingProvider')]
    public function testAcceptsAllCommonLineEndingsForIdentifierExpectations(string $lineEnding): void
    {
        $source = '// @akashi-phpstan-error argument.type'
            . $lineEnding
            . '$value = invalid();';

        $expectations = (new ExpectationParser())->parse($this->example($source));

        self::assertCount(1, $expectations);
        self::assertSame('argument.type', $expectations[0]->identifier);
        self::assertSame(['first' => 21, 'last' => 21], $expectations[0]->sourceLineRange);
    }

    public function testIgnoresIdentifierLikeTextInsideAString(): void
    {
        $source = <<<'PHP'
$text = <<<'MARKER'
// @akashi-phpstan-error argument.type
MARKER;
PHP;

        self::assertSame([], (new ExpectationParser())->parse($this->example($source)));
    }

    private function example(string $source): Example
    {
        $lineBreaks = preg_match_all('/\r\n|\r|\n/', $source);
        if ($lineBreaks === false) {
            throw new \LogicException('Unable to count fixture source lines.');
        }

        $lineCount = $lineBreaks + 1;
        if (preg_match('/(?:\r\n|\r|\n)\z/', $source) === 1) {
            --$lineCount;
        }

        $sourceLength = strlen($source);
        $firstCodeLine = 20;
        $lastCodeLine = $firstCodeLine + $lineCount - 1;

        return Example::fromInline(
            id: new ExampleId('phpstan-example-01'),
            label: 'PHPStan expectation fixture',
            document: new Document('docs/phpstan.md', $source),
            location: new SourceLocation(
                $firstCodeLine - 1,
                $firstCodeLine,
                $lastCodeLine,
                $lastCodeLine + 1,
                new SourceSpan(0, $sourceLength),
                new SourceSpan(0, $sourceLength),
            ),
            language: new Language('php'),
            code: new ExampleCode($source),
            fence: new FenceMetadata('php', '`', 3, 0),
            ordinal: 1,
        );
    }
}
