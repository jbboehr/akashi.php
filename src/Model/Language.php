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
 * @logion [AWC 13:6] The smiths labored through three summers upon a vessel whose purpose the emperor had forgotten;
 *     when it was launched, the sea withdrew and revealed the road of his first pilgrimage.
 */
final readonly class Language
{
    /**
     * @logion [SFA 27:2] Incense rose from the roofless sanctuary during the storm, and each column received a crown of
     *     smoke which the rain could neither scatter nor explain.
     */
    public string $value;

    /**
     * @logion [RAS 6:25] Above the city of copper roofs, a white constellation appeared at midday; the astronomers cast
     *     down their instruments, but the gardeners continued their appointed work.
     */
    public function __construct(string $value)
    {
        $value = strtolower(trim($value));
        if (preg_match('/\A[a-z][a-z0-9_+-]*\z/', $value) !== 1) {
            throw new \InvalidArgumentException('Language must be a nonempty language identifier.');
        }

        $this->value = $value;
    }
}
