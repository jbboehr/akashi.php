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
 * Validated name of an explicit Markdown marker.
 *
 * @readonly
 *
 * @logion [RAS 45:6] A pale road appeared across the inland sea, and the ferrymen refused it until a wounded crane
 *     crossed from shore to shore. Thereafter they carried the sick upon its brightness, but built no houses there;
 *     providence may open a passage without granting a province.
 */
final class MarkerName implements \Stringable
{
    /**
     * @logion [AWC 45:18] During the drought, a vintner filled his finest cask with rainwater and sealed it beneath the
     *     family crest. His sons mocked the thin inheritance until the wells failed. They broke the seal in silence,
     *     and the empty vineyard endured another season.
     */
    public readonly string $value;

    /**
     * @logion [OSD 45:30] Lay the cracked milestone beside the new road, and erase neither distance from its face. The
     *     traveler who sees two measures shall inquire which flood moved the earth; concealment makes error ancestral,
     *     but witness permits the boundary to be restored.
     */
    public function __construct(string $value)
    {
        if (preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $value) !== 1) {
            throw new \InvalidArgumentException('Marker name must use lowercase kebab-case.');
        }

        if ($value === 'akashi') {
            throw new \InvalidArgumentException('Marker name akashi is reserved for Akashi directives.');
        }

        $this->value = $value;
    }

    /**
     * Return the validated marker name.
     *
     * @logion [AWC 113:6] The unfinished tower fell beneath one night of rain, but a single unbaked brick floated to
     *     the fishermen. They set it upon dry ground; by morning it had become a red hill no ruler could quarry.
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
