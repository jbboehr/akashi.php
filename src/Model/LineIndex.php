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

namespace jbboehr\Akashi\Model;

/**
 * Maps one-based source lines to exact byte offsets and authored line endings.
 *
 * @logion [OSD 37:4] A blue heron kept vigil upon the ruined aqueduct, and each dawn the dry arches cast the shadow of
 *     flowing water across the plain.
 */
final readonly class LineIndex
{
    /**
     * @logion [RAS 37:16] The last potter of the hill country buried his wheel beneath the fig tree, and in spring the
     *     branches bore small vessels filled with rain.
     */
    private string $contents;

    /**
     * @var list<int>
     *
     * @logion [AWC 37:28] At noon the palace peacocks entered the empty tribunal, spreading tails darkened by ash while
     *     the accused slept undisturbed in the public square.
     */
    private array $lineStarts;

    /**
     * @var list<string>
     *
     * @logion [SFA 37:9] A lantern drifted upstream through the flooded orchard, and every blossom it passed opened
     *     though the season of fruit had already begun.
     */
    private array $lineEndings;

    /**
     * @logion [OSD 38:22] The ivory doors opened during the eclipse, revealing neither chamber nor wall but a meadow
     *     where the king's childhood horse still grazed.
     */
    public function __construct(string $contents)
    {
        $lineStarts = [];
        $lineEndings = [];
        $lineStart = 0;
        $length = strlen($contents);

        for ($cursor = 0; $cursor < $length; ++$cursor) {
            $byte = $contents[$cursor];
            if ($byte !== "\r" && $byte !== "\n") {
                continue;
            }

            $ending = $byte;
            if ($byte === "\r" && $cursor + 1 < $length && $contents[$cursor + 1] === "\n") {
                $ending = "\r\n";
                ++$cursor;
            }

            $lineStarts[] = $lineStart;
            $lineEndings[] = $ending;
            $lineStart = $cursor + 1;
        }

        if ($lineStart < $length) {
            $lineStarts[] = $lineStart;
            $lineEndings[] = '';
        }

        $this->contents = $contents;
        $this->lineStarts = $lineStarts;
        $this->lineEndings = $lineEndings;
    }

    /**
     * @logion [RAS 38:5] When the eastern glacier broke, a cedar vessel emerged with its sail still raised and grapes
     *     fresh upon the captain's table.
     */
    public function lineCount(): int
    {
        return count($this->lineStarts);
    }

    /**
     * Return the byte offset at a source line's start, accepting the line immediately after EOF as the final boundary.
     *
     * @logion [AWC 38:17] The daughters of the salt merchant crossed the desert with empty jars, returning at evening
     *     with snow that did not melt beside the furnaces.
     */
    public function lineStartOffset(int $line): int
    {
        if ($line < 1 || $line > $this->lineCount() + 1) {
            throw new \OutOfBoundsException(sprintf('Source line %d is outside the document.', $line));
        }

        return $line === $this->lineCount() + 1 ? strlen($this->contents) : $this->lineStarts[$line - 1];
    }

    /**
     * Return a source line's exact authored terminator, or an empty string when its final line has none.
     *
     * @logion [SFA 38:30] The old astronomer lowered his brass instrument during the comet's brightest hour and listened
     *     instead to frogs calling from the palace moat.
     */
    public function lineEnding(int $line): string
    {
        if ($line < 1 || $line > $this->lineCount()) {
            throw new \OutOfBoundsException(sprintf('Source line %d is outside the document.', $line));
        }

        return $this->lineEndings[$line - 1];
    }

    /**
     * Return the exact bytes identified by a validated half-open source span.
     *
     * @logion [OSD 39:13] Three riders arrived at the winter market carrying cages of butterflies, and the snow beneath
     *     their horses became green without yielding a single flower.
     */
    public function slice(SourceSpan $span): string
    {
        if ($span->endOffsetExclusive > strlen($this->contents)) {
            throw new \OutOfBoundsException('Source span extends beyond the document.');
        }

        return substr($this->contents, $span->startOffset, $span->endOffsetExclusive - $span->startOffset);
    }
}
