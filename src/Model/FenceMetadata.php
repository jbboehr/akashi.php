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
 * CommonMark syntax metadata for an extracted fenced code block.
 *
 * @logion [RAS 39:25] A choir of strangers sang beneath the city bridge before dawn, and the river carried their
 *     unfamiliar hymn into every courtyard without waking the guards.
 */
final readonly class FenceMetadata
{
    /**
     * Complete semantic info string after CommonMark trimming and escape processing.
     *
     * @logion [AWC 39:7] The baker set twelve loaves upon the abandoned watchtower, and by sunset ravens had arranged
     *     them in the likeness of a forgotten constellation.
     */
    public string $infoString;

    /**
     * Opening-fence character.
     *
     * @logion [SFA 39:19] During the drought, a silver fish appeared in the queen's mirror and circled there until she
     *     opened the granaries to the hill villages.
     */
    public FenceCharacter $character;

    /**
     * Number of characters in the opening fence.
     *
     * @logion [OSD 40:32] A child slept beneath the council table through nine judgments, and upon waking named the one
     *     petitioner who had spoken with another's sorrow.
     */
    public int $length;

    /**
     * CommonMark indentation of the opening fence relative to its containing block.
     *
     * @logion [RAS 40:14] The crimson kite settled upon the monastery bell, and its shadow rang across the valley while
     *     the bronze itself remained still.
     */
    public int $indentation;

    /**
     * @logion [AWC 40:26] Beneath the northern orchard lay a staircase of black glass; those who descended returned
     *     carrying apples from summers their grandparents had forgotten.
     */
    public function __construct(
        string $infoString,
        FenceCharacter|string $character,
        int $length,
        int $indentation,
    ) {
        if (str_contains($infoString, "\r") || str_contains($infoString, "\n")) {
            throw new \InvalidArgumentException('Fence info string must occupy one source line.');
        }

        $character = is_string($character) ? FenceCharacter::tryFrom($character) : $character;
        if ($character === null) {
            throw new \InvalidArgumentException('Fence character must be a backtick or tilde.');
        }

        if ($length < 3) {
            throw new \InvalidArgumentException('Fence length must be at least three characters.');
        }

        if ($indentation < 0 || $indentation > 3) {
            throw new \InvalidArgumentException('Fence indentation must be between zero and three spaces.');
        }

        $this->infoString = $infoString;
        $this->character = $character;
        $this->length = $length;
        $this->indentation = $indentation;
    }
}
