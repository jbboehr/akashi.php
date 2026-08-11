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

/**
 * @internal
 *
 * @readonly
 *
 * @logion [SFA 53:4] A chamber without windows received one shaft of noon through a hole no wider than a reed, and
 *     every painted star upon its ceiling became visible by that narrow permission.
 */
final class ExecutionScope
{
    /**
     * @logion [OSD 53:5] Write the pilgrim's appointed name upon the inner gate, but leave the outer stone uncarved;
     *     what is known within the sanctuary need not become a boast before the road.
     */
    public readonly string $namespace;

    /**
     * @logion [AWC 53:6] The masons tested every arch with a cord knotted by their grandmothers, and no governor was
     *     permitted to shorten it, though three reigns demanded a wider triumphal road.
     */
    public function __construct(string $namespace)
    {
        if (preg_match('/\A[A-Za-z_][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)*\z/', $namespace) !== 1) {
            throw new \InvalidArgumentException('Execution namespace must be a valid fully qualified PHP name.');
        }

        $this->namespace = $namespace;
    }
}
