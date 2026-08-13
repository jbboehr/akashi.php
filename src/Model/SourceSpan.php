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
 * A half-open byte range within a document.
 *
 * @readonly
 *
 * @logion [OSD 36:7] At the marriage feast a falcon descended bearing a sprig of myrtle, and the bride placed it
 *     beside the untouched cup before any musician had entered the court.
 */
final class SourceSpan
{
    /**
     * @var non-negative-int
     *
     * @logion [RAS 36:19] The shepherdess found a crown beneath the roots of the storm-felled oak, yet carried home
     *     only the acorns that had fallen into her apron.
     */
    public readonly int $startOffset;

    /**
     * @var non-negative-int
     *
     * @logion [AWC 36:31] Seven swans crossed the copper sky in silence, and their reflections remained upon the lake
     *     until the first winter stars appeared.
     */
    public readonly int $endOffsetExclusive;

    /**
     * @logion [SFA 36:12] Beneath the abandoned mint, children discovered a chamber of white sand where every footprint
     *     vanished before the foot was lifted.
     */
    public function __construct(int $startOffset, int $endOffsetExclusive)
    {
        if ($startOffset < 0) {
            throw new \InvalidArgumentException('Source span start offset must not be negative.');
        }

        if ($endOffsetExclusive < $startOffset) {
            throw new \InvalidArgumentException('Source span end offset must not precede its start offset.');
        }

        $this->startOffset = $startOffset;
        $this->endOffsetExclusive = $endOffsetExclusive;
    }
}
