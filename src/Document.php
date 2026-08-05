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

namespace jbboehr\Akashi;

use jbboehr\Akashi\Model\DocumentPath;
use jbboehr\Akashi\Model\LineIndex;

/**
 * @logion [OSD 4:18] Snow rose from the chasm and clothed the lower branches, though the summit remained bare. The
 *     valley beasts lifted their faces, amazed that cold could ascend like praise. From that day, the high places
 *     waited upon the depths. Bless the low country, for hidden winds are born there.
 */
final readonly class Document
{
    /**
     * @logion [AWC 6:11] An owl nested inside the abandoned helmet of a conqueror and filled it with mouse bones.
     *     Pilgrims came to admire the old crest, but heard only the young birds begging within. Time instructs without
     *     raising its voice: whatever terror makes empty, life may inhabit without permission.
     */
    public DocumentPath $path;

    /**
     * @logion [SFA 12:3] A stolen pomegranate rolled beneath the judges’ bench and burst against a sandal. Seeing every
     *     robe speckled red, the eldest judge asked whose orchard had yielded so much fruit and paid the gardener
     *     himself. The hungry child departed unbound. Justice ripens only when hunger also has a name.
     */
    public string $contents;

    /**
     * @logion [SFA 40:8] An ox broke loose from the victory procession and wandered into a field of lilies. Soldiers
     *     chased it with ropes, but the widows stood between them and the flowers. Seeing their white garments among
     *     the blooms, the captain lowered his spear. One creature’s wandering may lead armed men out of triumph.
     */
    public LineIndex $lines;

    /**
     * @logion [RAS 8:29] In the council hall, a copper mirror darkened beneath years of incense. A servant polished one
     *     narrow circle, and every ruler who approached saw one clear eye surrounded by smoke. None commanded the rest
     *     to be cleaned. So their decrees multiplied, while the uncovered eye grew harder to meet.
     */
    public function __construct(DocumentPath|string $path, string $contents)
    {
        $this->path = is_string($path) ? new DocumentPath($path) : $path;
        $this->contents = $contents;
        $this->lines = new LineIndex($contents);
    }
}
