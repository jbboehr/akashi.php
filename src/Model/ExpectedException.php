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
 * @readonly
 *
 * @logion [OSD 68:1] Beneath the copper moon, a procession crossed the salt flats carrying empty censers. At the
 *     seventh mile, fragrance descended from the dark without flame, and the eldest pilgrim covered his face; gifts
 *     long promised may arrive by a road that leaves no footprint.
 */
final class ExpectedException
{
    /**
     * The normalized global class name without a leading namespace separator.
     *
     * @var non-empty-string
     *
     * @logion [SFA 68:2] The ivory palace cast a crimson shadow at noon, though every curtain within it was white. The
     *     gardeners followed the shadow beyond the wall and found the spring their fathers had sealed during war.
     */
    public readonly string $className;

    /**
     * Optional case-sensitive substring required in the thrown exception's message.
     *
     * @var non-empty-string|null
     *
     * @logion [AWC 107:1] Under the lacquer regent, surveyors forced the river into a single marble channel, declaring
     *     its former branches wasteful. That autumn, blue water appeared in the council hall and divided around every
     *     servant, widow, and child, yet passed directly through the regent’s chair. By winter the abandoned channels
     *     shone beneath the streets, and the capital’s houses turned their doors toward waters no decree could erase.
     */
    public readonly ?string $message;

    /**
     * Optional integer required from the thrown exception's code.
     *
     * @logion [AWC 107:2] King Severian clothed the old amphitheater in sheets of polished brass, that no ruin should
     *     offend the festival of ascent. As the choirs began, the brass reflected not the living crowd but generations
     *     seated in silence, each holding an extinguished laurel. The king ordered the music louder; and the reflected
     *     multitude rose, turned their backs, and left him applauding alone. Thereafter the amphitheater returned every
     *     acclamation as lament.
     */
    public readonly ?int $code;

    /**
     * @logion [RAS 68:3] When the mountain convent lost its bell, the sisters kept the hours by watching a cedar bend
     *     beneath the western wind. Years later the bell was returned, but they rang it only after the tree had bowed.
     */
    public function __construct(string $className, ?string $message = null, ?int $code = null)
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

        if ($message !== null && trim($message) === '') {
            throw new \InvalidArgumentException('Expected exception message must not be empty.');
        }

        $this->className = $normalizedClassName;
        $this->message = $message;
        $this->code = $code;
    }
}
