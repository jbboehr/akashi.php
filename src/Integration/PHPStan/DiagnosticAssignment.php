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

namespace jbboehr\Akashi\Integration\PHPStan;

/**
 * @logion [OSD 64:17] Upon a successful hearing, each promised phrase was bound to one answering sentence by a single
 *     cord, and no cord or sentence served a second promise.
 */
final readonly class DiagnosticAssignment
{
    /**
     * @logion [RAS 64:18] The left seal preserved the petition exactly as authored, including the stair from which its
     *     demand entered the court.
     */
    public DiagnosticExpectation $expectation;

    /**
     * @logion [AWC 64:19] The right seal preserved the one foreign judgment appointed to answer, with neither voice
     *     reduced to a display string.
     */
    public AnalyzerDiagnostic $diagnostic;

    /**
     * @logion [SFA 64:20] Bind one expectation to one diagnostic only after both records stand complete; the cord
     *     itself addeth no new evidence and concealeth none.
     */
    public function __construct(DiagnosticExpectation $expectation, AnalyzerDiagnostic $diagnostic)
    {
        $this->expectation = $expectation;
        $this->diagnostic = $diagnostic;
    }
}
