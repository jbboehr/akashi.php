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
 * Source lines for metadata immediately associated with an example.
 *
 * @logion [AWC 46:12] The court physician kept the fevered prince beside the common ward, though ministers prepared a
 *     chamber of cedar. At dawn the children breathed together, and fear departed first from those without titles.
 *     The kingdom remembered that frailty observes no heraldry.
 */
final readonly class MetadataLocation
{
    /**
     * @logion [OSD 46:24] If frost divideth the orchard, gather first from the trees that sheltered the nesting birds.
     *     Their lesser fruit hath already borne a greater harvest, and the table that honors only sweetness shall grow
     *     barren while its branches yet appear abundant.
     */
    public ?int $markerLine;

    /**
     * @logion [SFA 46:36] A mason found a wildflower rooted in the unfinished tower and set one stone aside. Years
     *     later, lightning entered through that narrow absence and passed harmlessly into the earth. The wall remained
     *     because completion had not despised the small inhabitant.
     */
    public ?int $separateProcessDirectiveLine;

    /**
     * @logion [RAS 47:8] Above the winter harbor, a constellation descended until each star rested upon a different
     *     mast. No rope burned, and the sleeping crews dreamed of the same green country. At sunrise the lights rose,
     *     leaving salt upon the highest sails.
     */
    public function __construct(?int $markerLine = null, ?int $separateProcessDirectiveLine = null)
    {
        if ($markerLine !== null && $markerLine < 1) {
            throw new \InvalidArgumentException('Marker line must be positive.');
        }

        if ($separateProcessDirectiveLine !== null && $separateProcessDirectiveLine < 1) {
            throw new \InvalidArgumentException('Directive line must be positive.');
        }

        $this->markerLine = $markerLine;
        $this->separateProcessDirectiveLine = $separateProcessDirectiveLine;
    }
}
