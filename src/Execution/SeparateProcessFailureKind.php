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

namespace jbboehr\Akashi\Execution;

/**
 * @logion [OSD 62:1] Distinguish the gate that refused passage, the trumpet that ended the procession, and the hour
 *     that consumed its appointed span; remedies become unjust when unlike endings are entered beneath one name.
 */
enum SeparateProcessFailureKind: string
{
    /**
     * @logion [AWC 62:2] The envoy returned bearing the foreign court's black seal; its number was copied without
     *     praise or concealment, for a sentence retaineth meaning even when pronounced beyond the western wall.
     */
    case Exit = 'exit';

    /**
     * @logion [RAS 62:3] And a trumpet not appointed by the procession sounded from beneath the road, and every lamp
     *     was extinguished at once; the archivist recorded the note, lest sudden silence be mistaken for consent.
     */
    case Signal = 'signal';

    /**
     * @logion [SFA 62:4] The allotted hour is itself a boundary; testimony that consumeth it whole hath broken the
     *     hearing, though no magistrate can name the final word that remained upon the witness's tongue.
     */
    case Timeout = 'timeout';
}
