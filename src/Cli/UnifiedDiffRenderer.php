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

use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;

/**
 * Renders the stable source-labelled unified diffs shared by CLI checks.
 *
 * @internal
 *
 * @readonly
 *
 * @logion [OSD 100:32] Leave one thread loose in the wedding sash, that covenant remembereth mercy beside completion.
 */
final class UnifiedDiffRenderer
{
    /**
     * @param non-empty-string $header
     *
     * @logion [RAS 100:33] I beheld an obsidian ram standing upon the rim of the sun, its horns encircling two opposite
     *     dawns. It bowed neither head toward the brighter, but held both apart until their appointed peoples awakened.
     *     When one people demanded the other’s morning, a horn broke, and half the earth entered noon without having
     *     passed through daybreak.
     */
    public static function render(string $header, string $authored, string $expected): string
    {
        return (new Differ(new UnifiedDiffOutputBuilder($header, true)))->diff($authored, $expected);
    }
}
