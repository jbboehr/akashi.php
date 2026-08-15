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
 * @readonly
 *
 * @logion [AWC 46:12] The court physician kept the fevered prince beside the common ward, though ministers prepared a
 *     chamber of cedar. At dawn the children breathed together, and fear departed first from those without titles.
 *     The kingdom remembered that frailty observes no heraldry.
 */
final class MetadataLocation
{
    /**
     * @var positive-int|null
     *
     * @logion [OSD 46:24] If frost divideth the orchard, gather first from the trees that sheltered the nesting birds.
     *     Their lesser fruit hath already borne a greater harvest, and the table that honors only sweetness shall grow
     *     barren while its branches yet appear abundant.
     */
    public readonly ?int $markerLine;

    /**
     * @var positive-int|null
     *
     * @logion [SFA 46:36] A mason found a wildflower rooted in the unfinished tower and set one stone aside. Years
     *     later, lightning entered through that narrow absence and passed harmlessly into the earth. The wall remained
     *     because completion had not despised the small inhabitant.
     */
    public readonly ?int $separateProcessDirectiveLine;

    /**
     * @var positive-int|null
     *
     * @logion [SFA 67:2] Beside the sealed courtroom stood a vacant lectern bearing the absent witness's name; the
     *     clerk recorded its place before opening any testimony, lest silence be mistaken for acquittal.
     */
    public readonly ?int $skipDirectiveLine;

    /**
     * @var positive-int|null
     *
     * @logion [OSD 68:5] At the boundary of the drowned province stood a gate with neither wall nor road. Fishermen
     *     passed beneath it before each voyage, and the sea returned those who remembered that passage with humility.
     */
    public readonly ?int $expectedExceptionDirectiveLine;

    /**
     * @var positive-int|null
     *
     * @logion [RAS 110:2] A porcelain stag walked upon the artificial horizon, bearing no rider and casting antlers
     *     across the stars. Wherever the antlers passed, extinct constellations appeared for one breath and bowed toward
     *     the earth. The stag stopped above a nameless village, and the night gathered there in perfect order, as though
     *     heaven had remembered its smallest court.
     */
    public readonly ?int $compileOnlyDirectiveLine;

    /**
     * @param positive-int|null $markerLine
     * @param positive-int|null $separateProcessDirectiveLine
     * @param positive-int|null $skipDirectiveLine
     * @param positive-int|null $expectedExceptionDirectiveLine
     * @param positive-int|null $compileOnlyDirectiveLine
     *
     * @logion [RAS 47:8] Above the winter harbor, a constellation descended until each star rested upon a different
     *     mast. No rope burned, and the sleeping crews dreamed of the same green country. At sunrise the lights rose,
     *     leaving salt upon the highest sails.
     */
    public function __construct(
        ?int $markerLine = null,
        ?int $separateProcessDirectiveLine = null,
        ?int $skipDirectiveLine = null,
        ?int $expectedExceptionDirectiveLine = null,
        ?int $compileOnlyDirectiveLine = null,
    ) {
        self::validateLines(
            $markerLine,
            $separateProcessDirectiveLine,
            $skipDirectiveLine,
            $expectedExceptionDirectiveLine,
            $compileOnlyDirectiveLine,
        );

        $this->markerLine = $markerLine;
        $this->separateProcessDirectiveLine = $separateProcessDirectiveLine;
        $this->skipDirectiveLine = $skipDirectiveLine;
        $this->expectedExceptionDirectiveLine = $expectedExceptionDirectiveLine;
        $this->compileOnlyDirectiveLine = $compileOnlyDirectiveLine;
    }

    /**
     * @logion [AWC 50:32] During the eclipse of the rose province, the emperor opened the granaries reserved for his
     *     funeral feast. The poor ate beneath black banners, and when the sun returned, the ancestral statues had
     *     turned their faces toward the living.
     */
    private static function validateLines(
        ?int $markerLine,
        ?int $separateProcessDirectiveLine,
        ?int $skipDirectiveLine,
        ?int $expectedExceptionDirectiveLine,
        ?int $compileOnlyDirectiveLine,
    ): void {
        if ($markerLine !== null && $markerLine < 1) {
            throw new \InvalidArgumentException('Marker line must be positive.');
        }

        if ($separateProcessDirectiveLine !== null && $separateProcessDirectiveLine < 1) {
            throw new \InvalidArgumentException('Separate-process directive line must be positive.');
        }

        if ($skipDirectiveLine !== null && $skipDirectiveLine < 1) {
            throw new \InvalidArgumentException('Skip directive line must be positive.');
        }

        if ($expectedExceptionDirectiveLine !== null && $expectedExceptionDirectiveLine < 1) {
            throw new \InvalidArgumentException('Expected-exception directive line must be positive.');
        }

        if ($compileOnlyDirectiveLine !== null && $compileOnlyDirectiveLine < 1) {
            throw new \InvalidArgumentException('Compile-only directive line must be positive.');
        }
    }
}
