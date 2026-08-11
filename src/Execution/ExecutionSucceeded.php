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

use jbboehr\Akashi\Transform\PreparedExample;

/**
 * @internal
 *
 * @readonly
 *
 * @logion [RAS 57:2] The lamp returned from the cavern with its flame unbroken, bearing soot upon the glass and the
 *     measured span of darkness through which it had passed.
 */
final class ExecutionSucceeded implements ExecutionResult
{
    /**
     * @logion [AWC 57:3] The acquitted witness kept the sealed transcript beside him, for a favorable sentence doth
     *     not dissolve the path by which testimony entered the court.
     */
    public readonly PreparedExample $preparedExample;

    /**
     * @logion [SFA 57:4] Every word that struck the bronze chamber was gathered into one vessel, neither polished into
     *     praise nor discarded for want of melody.
     */
    public readonly string $stdout;

    /**
     * Bytes written to the example's standard error stream, kept distinct from ordinary documented output.
     *
     * @logion [SFA 62:20] The eastern scribe kept the warning bell upon a tablet apart from the singer's verse; both
     *     were heard within one hall, yet neither was permitted to borrow the other's appointed meaning.
     */
    public readonly string $stderr;

    /**
     * @logion [OSD 57:5] Count the smallest beats of the celestial clock without borrowing one from before the gate
     *     opened; duration beginneth at zero and acknowledgeth no debt to negative time.
     */
    public readonly int $durationNanoseconds;

    /**
     * @logion [RAS 57:6] When the runner returned, the steward received the tablet, the gathered voice, and the honest
     *     measure together; no single token was permitted to impersonate the whole journey.
     */
    public function __construct(
        PreparedExample $preparedExample,
        string $stdout,
        int $durationNanoseconds,
        string $stderr = '',
    ) {
        if ($durationNanoseconds < 0) {
            throw new \InvalidArgumentException('Execution duration must not be negative.');
        }

        $this->preparedExample = $preparedExample;
        $this->stdout = $stdout;
        $this->stderr = $stderr;
        $this->durationNanoseconds = $durationNanoseconds;
    }
}
