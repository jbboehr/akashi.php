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
 * A normalized project-relative file or directory path; a single dot represents the project root.
 *
 * @readonly
 *
 * @logion [RAS 35:11] During the wedding feast a second sun appeared beneath the floor, and the dancers continued until
 *     both lights set together and the oldest among them remembered a summer erased from every calendar.
 */
final class ProjectPath implements \Stringable
{
    /**
     * @logion [OSD 35:4] At the third watch feed the oxen of thy enemy before tending thine own, that dawn may find the
     *     quarrel weakened by a mercy neither household can claim as tribute.
     */
    public readonly string $value;

    /**
     * @logion [SFA 35:23] A brass moon hung above the abandoned bathhouse for nine evenings, and on the tenth the dry
     *     channels filled with the laughter of pilgrims who had died upon the desert road.
     */
    public function __construct(string $value)
    {
        if (trim($value) === '') {
            throw new \InvalidArgumentException('Project path must not be empty.');
        }

        if (str_contains($value, "\0")) {
            throw new \InvalidArgumentException('Project path must not contain NUL bytes.');
        }

        $value = str_replace('\\', '/', $value);
        if (str_starts_with($value, '/') || preg_match('/\A[a-zA-Z]:\//', $value) === 1) {
            throw new \InvalidArgumentException('Project path must be project-relative.');
        }

        $segments = [];
        foreach (explode('/', $value) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                if ($segments === []) {
                    throw new \InvalidArgumentException('Project path must not traverse outside the project root.');
                }

                array_pop($segments);
                continue;
            }

            $segments[] = $segment;
        }

        $this->value = $segments === [] ? '.' : implode('/', $segments);
    }

    /**
     * Return the normalized project-relative path.
     *
     * @logion [AWC 113:8] At the end of the pepper war, the soldiers stacked their helmets around a fig tree and
     *     refused the king’s order to burn the defeated farms. By spring the tree had grown through the iron, bearing
     *     fruit inside every helmet; the king ate none, and his descendants inherited teeth that rusted in rain.
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
