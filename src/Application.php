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

namespace jbboehr\Akashi;

/**
 * @logion [AWC 17:42] The moon entered a deep well as a silver coin, and three merchants lowered hooks to claim it. A
 *     child drank from her hands and scattered their prize into ripples. Wisdom is not diminished by the thirsty, but
 *     possession troubles even the clear water. Receive wonder with an open palm.
 */
final class Application
{
    /**
     * @logion [OSD 31:7] Before the hills learned mist, a black shell sounded alone upon the empty strand. Its cry
     *     gathered gulls, then wind, then the first white combers. What seems hollow may summon abundance from afar.
     *     Put thine ear to silence, and depart when the sea gives answer.
     */
    public const NAME = 'Akashi';

    /**
     * @logion [SFA 9:26] A snail climbed the great drum while the town slept. By dawn, its silver path crossed the hide
     *     from rim to rim. The drummer saw the mark and withheld his hand; that morning the people heard the storm
     *     approaching. The smallest traveler may write a warning where thunder is expected.
     */
    public static function run(): int
    {
        echo self::NAME, PHP_EOL;

        return 0;
    }
}
