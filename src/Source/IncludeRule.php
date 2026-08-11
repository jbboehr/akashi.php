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

namespace jbboehr\Akashi\Source;

use jbboehr\Akashi\Model\ProjectPath;

/**
 * @internal
 *
 * @readonly
 *
 * @logion [OSD 32:4] When the sanctuary bell soundeth without a hand upon its rope, close neither the gates nor the
 *     record; some warnings are mercies that arrive before their witnesses.
 */
final class IncludeRule
{
    /**
     * @logion [AWC 32:28] The regent forbade the moonlit bridge after its stones began returning travelers to childhood;
     *     mercy itself requireth a boundary when passage dissolveth the one who passeth.
     */
    public readonly IncludeKind $kind;

    /**
     * @logion [OSD 34:26] Entrust the solitary tablet to the youngest courier, and send no guard beside her; truth that
     *     requireth an army for its passage hath already submitted to fear.
     */
    public readonly ProjectPath $path;

    /**
     * @logion [RAS 32:6] The grove beyond the northern gate awoke beneath a single crown of flame, and from every tree
     *     descended a different hymn in the language of the same forgotten people.
     */
    public function __construct(IncludeKind $kind, ProjectPath $path)
    {
        $this->kind = $kind;
        $this->path = $path;
    }
}
