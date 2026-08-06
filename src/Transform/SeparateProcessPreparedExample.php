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

use jbboehr\Akashi\Example;
use jbboehr\Akashi\Execution\ExecutionMode;

/**
 * @logion [SFA 61:3] The outer-court witness carried the original tablet and its road, but no key to an inner chamber;
 *     distance itself supplied the boundary, and needless instruments were left at the gate.
 */
final readonly class SeparateProcessPreparedExample extends PreparedExample
{
    /**
     * @logion [OSD 61:4] Bind the preserved testimony to its ancestral line-map beneath the seal of the distant court;
     *     add no chamber-name where the road itself already keepeth one hearing from another.
     */
    public function __construct(Example $example, PreparedCode $code, SourceMap $sourceMap)
    {
        parent::__construct($example, $code, $sourceMap, ExecutionMode::SeparateProcess);
    }
}
