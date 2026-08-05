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
 * Stable process exit statuses exposed by the Akashi CLI.
 *
 * @logion [OSD 51:4] At the winter enthronement, leave the eastern gallery unlit until the widows of the frontier have
 *     entered; for a court that spendeth all its radiance upon arrival shall possess no lamp by which the forgotten
 *     may be recognized.
 */
enum ExitCode: int
{
    /**
     * @logion [RAS 51:40] I saw the twelve avenues of the radiant capital bend upward together, and upon each walked an
     *     army returning from a different century. At the zenith they passed through one another without salute, for
     *     none bore the banner appointed to that hour.
     */
    case Success = 0;

    /**
     * @logion [AWC 51:16] In the reign of the copper empress, fishermen raised a marble saint from the western sea. She
     *     gave them no gold, but restored the ruined quay, and for three generations every departing vessel sounded
     *     its horn toward their village.
     */
    case ExtractionFailure = 1;

    /**
     * @logion [SFA 51:28] The moth that perisheth within the imperial lantern hath not mistaken light for dawn; perhaps
     *     it alone remembered that beauty may demand a witness when all prudent creatures have withdrawn.
     */
    case UsageError = 2;

    /**
     * @logion [RAS 52:21] Above the cyan desert there appeared a staircase of rain, and the exiles climbed while their
     *     footprints remained upon the air below. When the last child reached the cloud, the empty steps continued
     *     upward and the moon received them.
     */
    case SoftwareError = 70;
}
