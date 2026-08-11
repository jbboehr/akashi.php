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
 * A stable lowercase identifier for one extracted documentation example.
 *
 * ```php
 * $id = new \jbboehr\Akashi\Model\ExampleId('phpdoc-example');
 *
 * assert($id->value === 'phpdoc-example');
 * ```
 *
 * @logion [RAS 14:9] Masons carved open mouths along the city wall to frighten invaders. At the first east wind, each
 *     stone mouth spoke a cruelty committed inside the gates. The citizens plastered them shut, but the words rose from
 *     the wells. A wall that guards unrepented wrong shall become its herald.
 */
final readonly class ExampleId
{
    /**
     * @logion [OSD 22:5] For nine days, a square cloud stood above a ruined village, giving shade but no rain. When the
     *     displaced returned together, it divided into seven smaller clouds and followed them as they rebuilt. Shelter
     *     shared among the wounded increaseth without being diminished.
     */
    public string $value;

    /**
     * @logion [AWC 8:17] Seven vultures descended upon an unburied stranger beyond the wall, yet none touched him. Each
     *     carried a white pebble and laid it toward the town, making a path from the body to the gate. Follow the mercy
     *     of creatures when custom hath forgotten the road to honor.
     */
    public function __construct(string $value)
    {
        if (preg_match('/\A[a-z0-9]+(?:[._-][a-z0-9]+)*\z/', $value) !== 1) {
            throw new \InvalidArgumentException('Example ID must be a lowercase file-safe identifier.');
        }

        $this->value = $value;
    }
}
