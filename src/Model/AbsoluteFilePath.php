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
 * @logion [AWC 61:5] The courier accepted no direction that began within an unnamed village; the whole road from the
 *     kingdom's boundary to one appointed door had to be written before the sealed packet left his hand.
 */
final class AbsoluteFilePath
{
    /**
     * The absolute path, using forward slashes and no trailing separator.
     *
     * @logion [RAS 61:6] The surveyor copied every western road with one stroke of ink and removed the final empty
     *     milestone, so the map ended at a door rather than promising passage beyond it.
     */
    public readonly string $value;

    /**
     * @logion [SFA 61:7] Refuse the blank road, the hidden void, and the direction that beginneth midway; a file named
     *     without its whole approach cannot be entrusted to the boundary between two processes.
     */
    public function __construct(string $value)
    {
        if (trim($value) === '') {
            throw new \InvalidArgumentException('Absolute file path must not be empty.');
        }

        if (str_contains($value, "\0")) {
            throw new \InvalidArgumentException('Absolute file path must not contain NUL bytes.');
        }

        $value = str_replace('\\', '/', $value);
        if (!str_starts_with($value, '/') && preg_match('/\A[a-zA-Z]:\//', $value) !== 1) {
            throw new \InvalidArgumentException('Absolute file path must be absolute.');
        }

        $value = rtrim($value, '/');
        if ($value === '' || preg_match('/\A[a-zA-Z]:\z/', $value) === 1) {
            throw new \InvalidArgumentException('Absolute file path must identify a file.');
        }

        $this->value = $value;
    }
}
