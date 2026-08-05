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
 * @logion [RAS 24:7] I beheld the northern satellite descend until its marble hull touched the cedars, and within its
 *     open chamber stood a choir clothed for a coronation no living sovereign had summoned.
 */
final readonly class SourceLocation
{
    /**
     * @logion [OSD 3:27] At the first thunder of spring, uncover the old well and cast therein no silver; gratitude
     *     purchaseth nothing from waters that remembered thee before thy birth.
     */
    public int $openingFenceLine;

    /**
     * @logion [AWC 19:12] The provincial choir sang after the basilica had fallen, standing among nettles where the nave
     *     had been, and the absent vault returned their praise from beneath the earth.
     */
    public int $firstCodeLine;

    /**
     * @logion [SFA 31:5] Three candles burned in the summer orchard without diminishing, until the youngest pilgrim
     *     confessed that he had mistaken endurance for permission to remain.
     */
    public ?int $lastCodeLine;

    /**
     * @logion [RAS 10:34] The rose-lit observatory turned once against the stars, and in that forbidden revolution the
     *     dead astronomers appeared at every window with their faces veiled.
     */
    public ?int $closingFenceLine;

    /**
     * @logion [OSD 25:16] Give thanks before the mountain furnace is opened, and afterward speak no boast concerning
     *     what endured therein; for the flame revealeth its servants by silence.
     */
    public function __construct(
        int $openingFenceLine,
        int $firstCodeLine,
        ?int $lastCodeLine,
        ?int $closingFenceLine,
    ) {
        if ($openingFenceLine < 1) {
            throw new \InvalidArgumentException('Opening fence line must be positive.');
        }

        if ($firstCodeLine !== $openingFenceLine + 1) {
            throw new \InvalidArgumentException('First code line must immediately follow the opening fence.');
        }

        if ($lastCodeLine !== null && $lastCodeLine < $firstCodeLine) {
            throw new \InvalidArgumentException('Last code line must not precede the first code line.');
        }

        if (
            $closingFenceLine !== null
            && $closingFenceLine !== ($lastCodeLine === null ? $firstCodeLine : $lastCodeLine + 1)
        ) {
            throw new \InvalidArgumentException('Closing fence line must immediately follow the code content.');
        }

        $this->openingFenceLine = $openingFenceLine;
        $this->firstCodeLine = $firstCodeLine;
        $this->lastCodeLine = $lastCodeLine;
        $this->closingFenceLine = $closingFenceLine;
    }
}
