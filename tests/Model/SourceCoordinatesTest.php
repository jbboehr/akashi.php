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

namespace jbboehr\Akashi\Tests\Model;

use jbboehr\Akashi\Model\FenceMetadata;
use jbboehr\Akashi\Model\FenceCharacter;
use jbboehr\Akashi\Model\LineIndex;
use jbboehr\Akashi\Model\SourceSpan;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SourceCoordinatesTest extends TestCase
{
    public function testIndexesMixedLineEndingsAndSlicesExactBytes(): void
    {
        $index = new LineIndex("a\r\nb\nc\rd");

        self::assertSame(4, $index->lineCount());
        self::assertSame([0, 3, 5, 7, 8], array_map($index->lineStartOffset(...), [1, 2, 3, 4, 5]));
        self::assertSame(["\r\n", "\n", "\r", ''], array_map($index->lineEnding(...), [1, 2, 3, 4]));
        self::assertSame("b\nc\r", $index->slice(new SourceSpan(3, 7)));
    }

    public function testIndexesAnEmptyDocumentWithoutInventingALine(): void
    {
        $index = new LineIndex('');

        self::assertSame(0, $index->lineCount());
        self::assertSame(0, $index->lineStartOffset(1));
        self::assertSame('', $index->slice(new SourceSpan(0, 0)));
    }

    public function testDoesNotInventALineAfterATerminalLineEnding(): void
    {
        $index = new LineIndex("a\n");

        self::assertSame(1, $index->lineCount());
        self::assertSame(2, $index->lineStartOffset(2));
    }

    #[DataProvider('invalidLineProvider')]
    public function testRejectsInvalidLineLookups(int $line, bool $ending): void
    {
        $index = new LineIndex("a\n");

        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage(sprintf('Source line %d is outside the document.', $line));

        if ($ending) {
            $index->lineEnding($line);
        } else {
            $index->lineStartOffset($line);
        }
    }

    /**
     * @return iterable<string, array{int, bool}>
     */
    public static function invalidLineProvider(): iterable
    {
        yield 'zero start' => [0, false];
        yield 'past boundary' => [3, false];
        yield 'zero ending' => [0, true];
        yield 'boundary has no ending' => [2, true];
    }

    public function testRejectsASpanBeyondTheDocument(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Source span extends beyond the document.');

        (new LineIndex('abc'))->slice(new SourceSpan(0, 4));
    }

    #[DataProvider('invalidSpanProvider')]
    public function testRejectsInvalidSourceSpans(int $start, int $end, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new SourceSpan($start, $end);
    }

    /**
     * @return iterable<string, array{int, int, string}>
     */
    public static function invalidSpanProvider(): iterable
    {
        yield 'negative start' => [-1, 0, 'Source span start offset must not be negative.'];
        yield 'reversed span' => [2, 1, 'Source span end offset must not precede its start offset.'];
    }

    public function testPreservesValidFenceMetadata(): void
    {
        $metadata = new FenceMetadata('PHP extra', FenceCharacter::Tilde, 5, 3);

        self::assertSame('PHP extra', $metadata->infoString);
        self::assertSame(FenceCharacter::Tilde, $metadata->character);
        self::assertSame(5, $metadata->length);
        self::assertSame(3, $metadata->indentation);
    }

    #[DataProvider('invalidFenceProvider')]
    public function testRejectsInvalidFenceMetadata(
        string $info,
        string $character,
        int $length,
        int $indentation,
        string $message,
    ): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new FenceMetadata($info, $character, $length, $indentation);
    }

    /**
     * @return iterable<string, array{string, string, int, int, string}>
     */
    public static function invalidFenceProvider(): iterable
    {
        yield 'multiline info CR' => ["php\r", '`', 3, 0, 'Fence info string must occupy one source line.'];
        yield 'multiline info LF' => ["php\n", '`', 3, 0, 'Fence info string must occupy one source line.'];
        yield 'invalid character' => ['php', '#', 3, 0, 'Fence character must be a backtick or tilde.'];
        yield 'short fence' => ['php', '`', 2, 0, 'Fence length must be at least three characters.'];
        yield 'negative indentation' => ['php', '`', 3, -1, 'Fence indentation must be between zero and three spaces.'];
        yield 'excess indentation' => ['php', '`', 3, 4, 'Fence indentation must be between zero and three spaces.'];
    }
}
