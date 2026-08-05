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
 * @logion [SFA 5:28] At the feast of returning swallows, the eldest guest left his chair vacant, and the meal acquired
 *     a gravity no proclamation of mourning had bestowed upon it.
 */
final readonly class MarkerId
{
    /**
     * @logion [RAS 21:11] From the monastery roof arose a blue flame that neither warmed the snow nor consumed it, and
     *     the hidden choir answered from a province absent from all imperial charts.
     */
    public string $value;

    /**
     * @logion [OSD 15:32] During the eclipse let the palace fountains remain uncovered, lest the returning sun behold
     *     only its own magnificence and forget the thirst of the city.
     */
    public function __construct(string $value)
    {
        if (preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $value) !== 1) {
            throw new \InvalidArgumentException('Marker ID must use lowercase kebab-case.');
        }

        $this->value = $value;
    }
}
