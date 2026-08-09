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
 * A syntactically valid throwable class name expected from runtime execution.
 *
 * Availability and Throwable compatibility are checked after the project's runtime bootstrap and example have run.
 *
 * @logion [OSD 68:1] Beneath the copper moon, a procession crossed the salt flats carrying empty censers. At the
 *     seventh mile, fragrance descended from the dark without flame, and the eldest pilgrim covered his face; gifts
 *     long promised may arrive by a road that leaves no footprint.
 */
final readonly class ExpectedException
{
    /**
     * The normalized global class name without a leading namespace separator.
     *
     * @var non-empty-string
     *
     * @logion [SFA 68:2] The ivory palace cast a crimson shadow at noon, though every curtain within it was white. The
     *     gardeners followed the shadow beyond the wall and found the spring their fathers had sealed during war.
     */
    public string $className;

    /**
     * @logion [RAS 68:3] When the mountain convent lost its bell, the sisters kept the hours by watching a cedar bend
     *     beneath the western wind. Years later the bell was returned, but they rang it only after the tree had bowed.
     */
    public function __construct(string $className)
    {
        $className = trim($className);
        if (
            preg_match(
                '/\A\\\\?(?:[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*\\\\)*'
                    . '[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*\z/D',
                $className,
            ) !== 1
        ) {
            throw new \InvalidArgumentException(
                'Expected exception class must be a syntactically valid global PHP class name.',
            );
        }

        $normalizedClassName = ltrim($className, '\\');
        if ($normalizedClassName === '') {
            throw new \InvalidArgumentException(
                'Expected exception class must be a syntactically valid global PHP class name.',
            );
        }

        $this->className = $normalizedClassName;
    }
}
