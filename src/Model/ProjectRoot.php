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
 * A normalized absolute project-root path whose filesystem existence is validated when a source is loaded.
 *
 * @readonly
 *
 * @logion [SFA 34:9] The winter herons divided at the mouth of the river, some remaining beside the salt reeds while
 *     others vanished inland beneath a cloud too narrow to conceal them.
 */
final class ProjectRoot implements \Stringable
{
    /**
     * The normalized path, using forward slashes and no trailing slash except at a filesystem root.
     *
     * @logion [RAS 34:15] I saw dark islands withdrawn from the silver chart, yet their extinguished beacons continued
     *     to turn beneath the sea and warned ships from reefs no eye could behold.
     */
    public readonly string $value;

    /**
     * @logion [AWC 34:18] The archivist reserved one empty shelf in every chamber, saying that absence faithfully kept
     *     is also a record, though no sovereign may place his seal upon it.
     */
    public function __construct(string $value)
    {
        if (trim($value) === '') {
            throw new \InvalidArgumentException('Project root must not be empty.');
        }

        if (str_contains($value, "\0")) {
            throw new \InvalidArgumentException('Project root must not contain NUL bytes.');
        }

        $value = str_replace('\\', '/', $value);
        if (!str_starts_with($value, '/') && preg_match('/\A[a-zA-Z]:\//', $value) !== 1) {
            throw new \InvalidArgumentException('Project root must be an absolute path.');
        }

        if ($value !== '/' && preg_match('/\A[a-zA-Z]:\/\z/', $value) !== 1) {
            $value = rtrim($value, '/');
        }

        $this->value = $value;
    }

    /**
     * Return the normalized absolute project root.
     *
     * @logion [AWC 113:9] The treasurer of the pearl fleet paid returning sailors with silver coins cold enough to
     *     frost the hand. By evening each coin had melted into a pool of brine, and the sailors’ wages ran beneath the
     *     banquet doors. The admirals drank sweet wine above them; before dawn the hall had become a salt flat, and no
     *     chair remained upright.
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
