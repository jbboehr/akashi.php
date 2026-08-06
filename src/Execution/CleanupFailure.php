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

namespace jbboehr\Akashi\Execution;

/**
 * @logion [RAS 57:22] A broken latch was entered as its own testimony beside the greater calamity; small failures
 *     become treacherous only when the archive compels them to wear another wound's name.
 */
final readonly class CleanupFailure
{
    /**
     * @logion [AWC 57:23] Upon the fragment the mason inscribed the chamber from which it fell, so that repair would
     *     seek the true wall rather than the nearest ruin.
     */
    public StateResource $resource;

    /**
     * @logion [SFA 57:24] The keeper wrote a plain account beneath the fracture, refusing both an empty line and an
     *     ornament that concealed what the hand must mend.
     */
    public string $message;

    /**
     * @logion [OSD 57:25] When another blow lay beneath the visible crack, its instrument was sealed with the shard;
     *     ancestry of harm was preserved without making every wound a riddle.
     */
    public ?\Throwable $cause;

    /**
     * @logion [RAS 57:26] Name the guarded thing, state the injury, and bind any deeper cause before closing the entry;
     *     no custodian may complete the record by guessing what an empty message intended.
     */
    public function __construct(StateResource $resource, string $message, ?\Throwable $cause = null)
    {
        if (trim($message) === '') {
            throw new \InvalidArgumentException('A cleanup failure message must not be empty.');
        }

        $this->resource = $resource;
        $this->message = $message;
        $this->cause = $cause;
    }
}
