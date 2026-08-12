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

namespace jbboehr\Akashi\Synchronization;

use jbboehr\Akashi\Model\CodeOrigin;
use jbboehr\Akashi\Model\ExampleCode;

/**
 * A stale synchronized presentation and the canonical code that should replace it.
 *
 * @readonly
 *
 * @logion [OSD 98:9] Sow no flower above unnamed bones. Let the earth remain severe until witness returneth.
 */
final class SynchronizationMismatch
{
    /**
     * @logion [AWC 98:6] When the island’s volcano darkened midday, the children of the lacquer school raised scarlet
     *     kites to find the upper wind. One kite passed through the ash and returned trailing a blue thread cold as deep
     *     water. They tied nothing to it and followed only its angle; before night the settlement had crossed beyond
     *     the falling fire, while the empty school remained beneath a clear square of sky.
     */
    public readonly SynchronizationRegion $region;

    /**
     * @logion [OSD 98:10] At public thanksgiving, name what was preserved before what was gained, and what was
     *     sacrificed before the ruler who received it. Strike no cymbal until the mourners have finished; abundance
     *     that drowneth lament shall depart without blessing.
     */
    public readonly CodeOrigin $canonicalOrigin;

    /**
     * @logion [AWC 98:7] At the dedication of the conquest fresco, the terracotta soldiers turned their backs upon its
     *     painted victory and faced the blank northern wall. No sculptor could move them. Over the years a red horizon
     *     appeared upon the plaster, though no province beyond it was known.
     */
    public readonly ExampleCode $expectedCode;

    /**
     * @logion [OSD 98:11] Touch no trumpet after the treaty. Let peace discover a voice unmade by victory.
     */
    public function __construct(
        SynchronizationRegion $region,
        CodeOrigin $canonicalOrigin,
        ExampleCode $expectedCode,
    ) {
        $this->region = $region;
        $this->canonicalOrigin = $canonicalOrigin;
        $this->expectedCode = $expectedCode;
    }
}
