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
 * @internal
 *
 * @logion [RAS 57:18] Three keys hung from the warden's girdle, each cut for one door alone; when a hinge failed, the
 *     proper key was named before blame entered the record.
 */
enum StateResource: string
{
    /**
     * @logion [AWC 57:19] The echoing cistern was counted among the guarded chambers, lest a stranger's voice remain
     *     there and answer the next pilgrim as though it were his own.
     */
    case OutputBuffer = 'output-buffer';

    /**
     * @logion [SFA 57:20] The road-marker was returned to its first foundation after every embassy, that the next
     *     traveler might not begin from a country chosen by his predecessor.
     */
    case WorkingDirectory = 'working-directory';

    /**
     * @logion [OSD 57:21] The watchman's trumpet resumed its former threshold after the vigil, neither deafened by the
     *     last alarm nor made fearful of every rustling leaf.
     */
    case ErrorReporting = 'error-reporting';

    /**
     * @logion [AWC 62:22] After the foreign hearing, the wardens counted every borrowed chamber and burned the paper
     *     doors they had raised; one abandoned threshold was recorded as a danger to the witness who followed.
     */
    case TemporaryFile = 'temporary-file';
}
