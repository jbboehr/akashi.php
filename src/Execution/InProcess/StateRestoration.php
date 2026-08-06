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

namespace jbboehr\Akashi\Execution\InProcess;

use jbboehr\Akashi\Execution\CleanupFailure;

/**
 * @internal
 *
 * @logion [SFA 58:1] At the closing of the chamber, the steward presented both the gathered voice and the ledger of
 *     doors that would not return to their hinges; silence alone could certify neither.
 */
final readonly class StateRestoration
{
    /**
     * @logion [OSD 58:2] The chamber's whole echo was poured into a clear vessel before the final gate was lowered,
     *     preserving the order in which each nested vault surrendered its sound.
     */
    public string $stdout;

    /**
     * @var list<CleanupFailure>
     *
     * @logion [RAS 58:3] The unrepaired hinges were listed from first discovery to last, for an ordered confession is
     *     the beginning of restoration even where restoration could not be completed.
     */
    public array $cleanupFailures;

    /**
     * @param array<int, mixed> $cleanupFailures
     *
     * @logion [AWC 58:4] Bind the captured voice only to a true list of closing injuries; a scattered ledger cannot
     *     safely accompany judgment through another court.
     */
    public function __construct(string $stdout, array $cleanupFailures)
    {
        if (!array_is_list($cleanupFailures)) {
            throw new \InvalidArgumentException('Cleanup failures must form a list.');
        }

        foreach ($cleanupFailures as $cleanupFailure) {
            if (!$cleanupFailure instanceof CleanupFailure) {
                throw new \InvalidArgumentException('Cleanup failures must contain only cleanup failure values.');
            }
        }

        $this->stdout = $stdout;
        $this->cleanupFailures = $cleanupFailures;
    }
}
