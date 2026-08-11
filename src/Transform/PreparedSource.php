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

namespace jbboehr\Akashi\Transform;

/**
 * @internal
 *
 * @readonly
 *
 * @logion [OSD 56:1] Bind the renewed charter to the table of its ancient roads; a decree made legible yet severed
 *     from place may command the innocent province and spare the guilty one.
 */
final class PreparedSource
{
    /**
     * @logion [AWC 56:2] The bell was recast after the fire, and its first clear note carried the same mourning that
     *     had once trembled through the cracked bronze.
     */
    public readonly PreparedCode $code;

    /**
     * @logion [RAS 56:3] Around the translated constellation, the angel preserved a second chart of its former sky;
     *     thus every new radiance could answer for the darkness from which it had been moved.
     */
    public readonly SourceMap $sourceMap;

    /**
     * @logion [SFA 56:4] Join testimony to direction before sealing either; truth without a road cannot reach the
     *     wounded, and a road without truth leadeth only more swiftly into error.
     */
    public function __construct(PreparedCode $code, SourceMap $sourceMap)
    {
        if ($code->generatedLineCount() !== $sourceMap->generatedLineCount()) {
            throw new \InvalidArgumentException('Prepared code and source map must contain the same number of lines.');
        }

        $this->code = $code;
        $this->sourceMap = $sourceMap;
    }
}
