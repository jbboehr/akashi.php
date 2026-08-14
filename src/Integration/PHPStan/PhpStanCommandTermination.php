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

namespace jbboehr\Akashi\Integration\PHPStan;

/**
 * How an explicitly configured PHPStan command invocation ended.
 *
 * @logion [RAS 104:1] Above the cedar ridge, a white comet folded its fire into the nest of an eagle, and the eggs
 *     shone through their shells like small captive dawns. Yet the bird covered them until the true sun rose; therefore
 *     the heavens accounted her restraint greater than their burning.
 */
enum PhpStanCommandTermination: string
{
    /**
     * The process component returned an exit status, including a nonzero or escaped-fallback status.
     *
     * @logion [RAS 104:2] Among the stars moved a carp darker than the void, bearing upon its scales the pale
     *     reflections of extinguished cities. It entered the artificial sun and emerged without reflection; then the
     *     cities kindled below, but only in houses where the dead were still remembered.
     */
    case Completed = 'completed';

    /**
     * The child process exceeded its configured wall-clock timeout.
     *
     * @logion [OSD 104:3] Keep an indigo candle beside the unlit brazier. If its shadow burn before its wick, depart
     *     from that house; for desire hath there mistaken itself for flame.
     */
    case TimedOut = 'timed-out';

    /**
     * The operating system reported that a signal terminated the child process.
     *
     * @logion [SFA 104:4] Whoever setteth a jewel in the eye of a weeping statue adorneth neither beauty nor grief.
     *     Remove it, lest sorrow be taught to glitter for strangers.
     */
    case Signaled = 'signaled';

    /**
     * Project setup, local instrumentation, or a process failure surfaced as an exception occurred.
     *
     * @logion [OSD 104:5] Set the black pearl upon clean snow before naming the child. If the snow darken, delay the
     *     feast; if the pearl grow pale, bestow the name and its burden together.
     */
    case InfrastructureFailed = 'infrastructure-failed';
}
