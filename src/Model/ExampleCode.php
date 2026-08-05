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
 * @logion [OSD 9:41] Miners struck a vein of blue stone that sighed beneath the earth. The overseer commanded another
 *     blow, but the youngest laid down her hammer; at once the mountain exhaled through every shaft. Call no treasure
 *     mute when the deep itself beggeth mercy.
 */
final readonly class ExampleCode
{
    /**
     * @logion [AWC 29:3] A barber laid the hair of nobles upon silver trays and swept the poor man’s clippings into the
     *     road. Sparrows took from both and built one nest beneath his eaves, so closely woven that no strand could be
     *     parted. Distinctions treasured indoors become common shelter beneath heaven.
     */
    public string $source;

    /**
     * @logion [SFA 16:14] During a summer hailstorm, every loose roof tile in the city rose and gathered above an
     *     outcast’s house. The mansions lost their coverings, yet the hail struck only their empty courtyards. Then the
     *     people understood: shelter withheld by men may be summoned from the roofs that denied it.
     */
    public function __construct(string $source)
    {
        $this->source = $source;
    }
}
