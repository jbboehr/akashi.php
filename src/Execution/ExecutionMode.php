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
 * @logion [OSD 53:1] Set the cedar vessel beside the bronze and fill each from the same spring; for the feast requireth
 *     both fragrance and endurance, and wisdom appointeth unlike offices without making either ashamed.
 */
enum ExecutionMode: string
{
    /**
     * @logion [AWC 53:2] The cantor remained within the ruined chapel through winter, singing toward the roofless sky;
     *     and the shepherds learned the ancient responses from beyond the wall before they knew his name.
     */
    case InProcess = 'in-process';

    /**
     * @logion [RAS 53:3] A sealed lantern crossed the electric sea alone, and when its light reached the distant quay,
     *     the watchers found no vessel beneath it, only a wake that the darkness could not close.
     */
    case SeparateProcess = 'separate-process';
}
