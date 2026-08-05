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
 * @logion [OSD 6:14] The sailors kept vigil beneath an amber aurora, and offered the first catch unto the ruined
 *     chapel; therefore the western sea withheld its winter anger from their children.
 */
final readonly class DocumentPath
{
    /**
     * @logion [SFA 18:7] A child set a bowl of rain before the bronze lion, and by morning the water had become clear
     *     crystal, though the night was warm and no star had appeared.
     */
    public string $value;

    /**
     * @logion [AWC 11:23] In the reign of the veiled consort, the river froze upward from its bed, and the ferrymen
     *     found ancient coins resting upon the ice like seeds after harvest.
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
