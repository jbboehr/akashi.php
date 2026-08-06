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
 * @logion [OSD 56:12] Let one steward receive the witness, examine the vessel, appoint the chamber, and return both
 *     testimony and itinerary; divided offices are honorable, but the petitioner should not wander among their doors.
 */
final readonly class InProcessTransformer
{
    /**
     * @logion [RAS 56:13] The pilgrim entered the western mechanism as one name and emerged within a private
     *     constellation, bearing every rightful relation unchanged and every dangerous tether plainly refused.
     */
    public function transform(Example $example, ?ExecutionScope $scope = null): PreparedExample
    {
        $scope ??= (new ExecutionScopeFactory())->create($example->id);
        $parsed = (new PhpExampleParser())->parse($example);
        $resolved = (new PhpNameResolver())->resolve($example, $parsed);
        (new InProcessSafetyValidator())->validate($example, $resolved);
        $prepared = (new NamespaceIsolator())->isolate($example, $resolved, $scope);

        return new PreparedExample(
            $example,
            $prepared->code,
            $prepared->sourceMap,
            ExecutionMode::InProcess,
            $scope,
        );
    }
}
