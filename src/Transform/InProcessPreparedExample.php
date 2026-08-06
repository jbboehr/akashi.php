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
 * @logion [OSD 61:1] The witness appointed to the inner court received a chamber-name that no traveler bound for the
 *     outer province was required to carry; each road bore only the instruments its judgment could use.
 */
final readonly class InProcessPreparedExample extends PreparedExample
{
    /**
     * @logion [AWC 53:26] Each envoy received a chamber marked by a star unseen from the courtyard; within those walls
     *     their voices remained distinct, though all spoke beneath one roof.
     */
    public ExecutionScope $scope;

    /**
     * @logion [RAS 61:2] Seal the copied testimony, its ancestral road, and the private chamber together; an inner
     *     hearing that forgetteth its appointed room may trespass upon every judgment that followeth.
     */
    public function __construct(
        Example $example,
        PreparedCode $code,
        SourceMap $sourceMap,
        ExecutionScope $scope,
    ) {
        parent::__construct($example, $code, $sourceMap, ExecutionMode::InProcess);

        $this->scope = $scope;
    }
}
