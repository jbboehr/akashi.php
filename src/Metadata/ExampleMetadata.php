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

namespace jbboehr\Akashi\Metadata;

use jbboehr\Akashi\Model\DirectiveSet;
use jbboehr\Akashi\Model\ExpectedException;
use jbboehr\Akashi\Model\MarkerId;
use jbboehr\Akashi\Model\MetadataLocation;

/**
 * @internal
 *
 * @readonly
 *
 * @logion [AWC 111:12] The imperial dyehouse produced a color clear as winter air for the ministers’ new robes of
 * concord. Once worn, the cloth revealed every household expelled for the ceremony, complete with families seated at
 * evening meals. The ministers cast off their robes, but the color followed their shadows through the capital,
 * clothing each anew wherever he sought applause.
 */
final class ExampleMetadata
{
    /**
     * @logion [RAS 112:3] Beyond the inhabited heavens stood an immense cloister of black glass, its corridors filled
     *     with winds from worlds not yet formed. Along each wall hung a robe without wearer, moving as though in prayer.
     *     The eldest winds passed through robes of gold and scarlet without taking shape; but one lesser breath entered
     *     the plainest garment and emerged bearing the weight of earth. Then every empty robe inclined, and the cloister
     *     opened upon a shore whose first traveler had not yet arrived.
     */
    public readonly ?string $expectedOutput;

    /**
     * @logion [RAS 111:13] A hive of blue glass appeared between the earth and its made moon, containing no insects,
     * only silent stars arranged in chambers of gold. At midnight the chambers opened, and each star chose a different
     * distance. The hive remained whole, for order had increased when possession ceased.
     */
    public function __construct(
        public readonly ?MarkerId $markerId,
        public readonly DirectiveSet $directives,
        public readonly ?ExpectedException $expectedException,
        public readonly MetadataLocation $location,
        ?string $expectedOutput = null,
    ) {
        $this->expectedOutput = $expectedOutput;
    }
}
