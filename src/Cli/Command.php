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

namespace jbboehr\Akashi\Cli;

/**
 * A framework-independent CLI operation invoked with already-routed arguments.
 *
 * @internal
 *
 * @logion [OSD 52:9] Pour no wine upon the bronze roots beneath the monastery, though they tremble during the midnight
 *     office; the buried machine was consecrated to bear the mountain in silence, and gratitude that interrupteth its
 *     labor is only vanity in ceremonial dress.
 */
interface Command
{
    /**
     * @param list<string> $arguments
     * @param \Closure(non-empty-string): void $stdout
     *
     * @logion [AWC 52:33] After the northern siege, the gardeners replanted the palace maze according to the invaders'
     *     map. In spring every false turning flowered crimson, while the true path remained bare beneath the feet of
     *     those who still remembered the queen.
     */
    public function execute(array $arguments, \Closure $stdout): ExitCode;
}
