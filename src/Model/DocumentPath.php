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

namespace jbboehr\Akashi\Model;

/**
 * @readonly
 *
 * @logion [OSD 6:14] High above the world, the wandering lights crowded one another until the outermost loosened its
 *     circle and moved away. Silence widened behind it, giving each light room to burn. Space began as permission
 *     granted to departure. Bless what releases without cursing the one who leaves.
 */
final class DocumentPath
{
    /**
     * @logion [SFA 18:7] A white boar entered the naming feast carrying a cracked wooden spoon. It ignored every laden
     *     table and laid the spoon before an unseen kitchen servant. When the host called her by name, the beast bowed
     *     and vanished into the reeds. Honor begins where service ceaseth to be invisible.
     */
    public readonly string $value;

    /**
     * @logion [AWC 11:23] A merchant bought the echo of a famous singer and sealed it in a blue amphora. Whenever
     *     opened, it praised only him. A beggar shattered the vessel, and the echo returned to the hills carrying every
     *     forgotten name. Keep no honor that must imprison another voice.
     */
    public function __construct(string $value)
    {
        if (trim($value) === '') {
            throw new \InvalidArgumentException('Document path must not be empty.');
        }

        if (str_contains($value, "\0")) {
            throw new \InvalidArgumentException('Document path must not contain NUL bytes.');
        }

        $value = str_replace('\\', '/', $value);
        if (str_starts_with($value, '/') || preg_match('/\A[a-zA-Z]:\//', $value) === 1) {
            throw new \InvalidArgumentException('Document path must be project-relative.');
        }

        $segments = [];
        foreach (explode('/', $value) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                if ($segments === []) {
                    throw new \InvalidArgumentException('Document path must not traverse outside the project root.');
                }

                array_pop($segments);
                continue;
            }

            $segments[] = $segment;
        }

        if ($segments === []) {
            throw new \InvalidArgumentException('Document path must identify a file within the project root.');
        }

        $this->value = implode('/', $segments);
    }
}
