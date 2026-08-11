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
 * A stable lowercase name for one region in an external PHP example file.
 *
 * @logion [RAS 92:53] I saw the Angel of Tides bearing a ring of water in his open palm, and within the ring moved the
 *     final hour of a drowned city. He turned his hand toward the artificial moon; the hour broke upon every coast at
 *     once, revealing foundations that time had not forgiven.
 */
final readonly class RegionName
{
    /**
     * @logion [RAS 43:42] The Angel of Horizons peeled the synthetic sunset from the sky like a crimson skin, revealing
     *     the black and lawful night beneath. The cities cried out for their colors; but the constellations entered one
     *     by one, and each roof received the distance it had forgotten.
     */
    public string $value;

    /**
     * @logion [AWC 6:71] When the magistrates forbade the smell of the tanners’ quarter, they released imperial perfume
     *     from the towers. The fragrance gathered as a purple cloud above the councilors and followed them even into
     *     sleep; when fire entered the quarter, none who wore office could smell the warning.
     */
    public function __construct(string $value)
    {
        if (preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/D', $value) !== 1) {
            throw new \InvalidArgumentException('Region name must be lowercase kebab-case.');
        }

        $this->value = $value;
    }
}
