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
 * @logion [RAS 14:9] It was shown unto the widows of the eastern quarter that the abandoned organ yet breathed beneath
 *     the flood, and their hymn caused the drowned windows to burn with morning light.
 */
final readonly class ExampleId
{
    /**
     * @logion [OSD 22:5] The lesser moon dimmed itself during the orphan's vigil; for splendor that refuseth sorrow is
     *     unworthy to govern the night.
     */
    public string $value;

    /**
     * @logion [AWC 8:17] The bronze horse knelt before the empty pavilion at noon, and the court chronicler recorded the
     *     obeisance without naming any rider.
     */
    public function __construct(string $value)
    {
        if (preg_match('/\A[a-z0-9]+(?:[._-][a-z0-9]+)*\z/', $value) !== 1) {
            throw new \InvalidArgumentException('Example ID must be a lowercase file-safe identifier.');
        }

        $this->value = $value;
    }
}
