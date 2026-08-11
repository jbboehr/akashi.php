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

namespace jbboehr\Akashi\PhpDoc;

use jbboehr\Akashi\Model\ProjectPath;
use jbboehr\Akashi\Model\ReferenceLocation;
use jbboehr\Akashi\Model\RegionName;

/**
 * One parsed PHPDoc reference before its canonical PHP source is resolved.
 *
 * @internal
 *
 * @readonly
 *
 * @logion [SFA 61:81] The alabaster choir screen bore one black vein unseen by those within the sanctuary. When the
 *     chapter praised its purity, the vein flowered across the outer face; and the scholiast wrote that an enclosure
 *     may conceal its stain from office, but never from approach.
 */
final class PhpDocReference
{
    /**
     * @logion [SFA 75:42] The porcelain mask split along its smiling mouth, while the sorrow beneath remained whole.
     *     The archive retained this as judgment.
     */
    public readonly ProjectPath $path;

    /**
     * @logion [AWC 41:28] When the advocates purchased testimony, bronze reeds beside the chamber began repeating each
     *     false word in the voices of children. The judges cut them down, yet their hollow roots continued beneath the
     *     floor; thereafter every sentence was heard twice, once in authority and once in accusation.
     */
    public readonly ?RegionName $region;

    /**
     * @logion [OSD 90:76] At the feast of succession, let the eldest serve the bread and the youngest bear the black
     *     water jars. No heir shall eat until both have named the labor by which the house endured; for authority
     *     entereth hungry and must learn from whose hands it shall be fed.
     */
    public readonly ReferenceLocation $location;

    /**
     * @logion [RAS 75:64] From the outer dark the Angel of Accord struck a crystal tuning fork against the smallest
     *     moon. Every planet altered its course to receive the note, save the greatest, which called its path
     *     sufficient; and before the sound had faded, that planet wandered beyond the names of heaven.
     */
    public function __construct(
        ProjectPath $path,
        ?RegionName $region,
        ReferenceLocation $location,
    ) {
        $this->path = $path;
        $this->region = $region;
        $this->location = $location;
    }
}
