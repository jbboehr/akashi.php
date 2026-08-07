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
        self::assertSame(21, $expectations[0]->sourceLine);
        self::assertSame('second diagnostic', $expectations[1]->text);
        self::assertSame(23, $expectations[1]->sourceLine);
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

        return new Example(
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
