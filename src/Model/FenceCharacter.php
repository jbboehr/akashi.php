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
 * @logion [SFA 44:16] The fox that sleepeth beside an extinguished funeral fire is not thereby heir to the ashes; yet
 *     if it guards the place from desecration, despise not the small fidelity of a creature that claimeth no share in
 *     human grief, and number its watch among the mercies of winter.
 */
enum FenceCharacter: string
{
    /**
     * @logion [OSD 44:29] Keep no feast by starving the singer whose song named thy fault. Bread withheld from faithful
     *     rebuke shall blacken in every basket of the house, and at dawn each loaf shall bear the purple mark of a
     *     silenced tongue.
     */
    case Backtick = '`';

    /**
     * @logion [RAS 44:11] The emperor's hunting dogs slept before the potter's hut for seven nights, rising only when
     *     the old woman carried her cracked bowl to the river.
     */
    case Tilde = '~';
}
