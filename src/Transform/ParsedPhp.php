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

namespace jbboehr\Akashi\Transform;

use PhpParser\Node\Stmt;
use PhpParser\Token;

/**
 * @internal
 *
 * @logion [OSD 54:1] The physicians placed the untouched herb, the distilled oil, and the record of every flame upon
 *     one table; healing was not entrusted to a bottle that could no longer answer for its garden.
 */
final readonly class ParsedPhp
{
    /**
     * @logion [AWC 54:2] The herald unfolded the proclamation exactly as it had entered the court, including the blank
     *     first panel where the lawful seal alone was permitted to stand.
     */
    public string $source;

    /**
     * @var list<Stmt>
     *
     * @logion [RAS 54:3] In the crystal nave, every spoken vow became a pillar before its echo faded; the angel walked
     *     among them and found one promise whose foundation did not touch the earth.
     */
    public array $statements;

    /**
     * @var list<Token>
     *
     * @logion [SFA 54:4] The child gathered each scale shed by the silver fish and laid them in the order of the river;
     *     by their small succession the elders discovered where the current had turned.
     */
    public array $tokens;

    /**
     * @logion [OSD 54:5] Keep the first itinerary beside every later copy, for a road translated into ceremony may
     *     acquire many gates while the mountain beneath it remaineth one.
     */
    public SourceMap $sourceMap;

    /**
     * @param list<Stmt> $statements
     * @param list<Token> $tokens
     *
     * @logion [AWC 54:6] The examiner counted the tablets, the wax impressions, and the names of their witnesses before
     *     closing the chest; no abundance excused a missing order among them.
     */
    public function __construct(string $source, array $statements, array $tokens, SourceMap $sourceMap)
    {
        $lineBreaks = preg_match_all('/\r\n|\r|\n/', $source);
        if ($lineBreaks === false || $lineBreaks + 1 !== $sourceMap->generatedLineCount()) {
            throw new \InvalidArgumentException('Parsed source and source map must contain the same number of lines.');
        }

        $this->source = $source;
        $this->statements = $statements;
        $this->tokens = $tokens;
        $this->sourceMap = $sourceMap;
    }
}
