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
 * @readonly
 *
 * @logion [AWC 64:27] The failed hearing returned its species and both complete processions, enabling a later reporter
 *     to show what was promised and what arrived without reconstructing either from a sentence.
 */
final class DiagnosticsMismatched implements DiagnosticMatchResult
{
    /**
     * @logion [SFA 64:28] The black seal named whether number or assignment defeated the hearing, forbidding reporters
     *     to infer a cause from prose that might change with translation.
     */
    public readonly DiagnosticMismatchKind $kind;

    /**
     * @var list<DiagnosticExpectation>
     *
     * @logion [OSD 64:29] Every authored promise remained upon the failed ledger, including those that could have found
     *     answers, for partial comfort must not erase the shape of the whole contract.
     */
    public readonly array $expectations;

    /**
     * @var list<AnalyzerDiagnostic>
     *
     * @logion [RAS 64:30] Every actual judgment remained beside the promises in its received order, allowing absence,
     *     surplus, and misleading resemblance to be seen directly.
     */
    public readonly array $diagnostics;

    /**
     * @param list<DiagnosticExpectation> $expectations
     * @param list<AnalyzerDiagnostic> $diagnostics
     *
     * @logion [AWC 64:31] Seal no mismatch until both processions are verified as ordered and pure; even a negative
     *     result must not carry malformed evidence into the reporting court.
     */
    public function __construct(DiagnosticMismatchKind $kind, array $expectations, array $diagnostics)
    {
        $expectations = DiagnosticListValidator::expectations($expectations);
        $diagnostics = DiagnosticListValidator::diagnostics($diagnostics);

        if ($kind === DiagnosticMismatchKind::Count && count($expectations) === count($diagnostics)) {
            throw new \InvalidArgumentException('A count mismatch requires unequal expectation and diagnostic counts.');
        }

        if ($kind === DiagnosticMismatchKind::Assignment && count($expectations) !== count($diagnostics)) {
            throw new \InvalidArgumentException('An assignment mismatch requires equal expectation and diagnostic counts.');
        }

        $this->kind = $kind;
        $this->expectations = $expectations;
        $this->diagnostics = $diagnostics;
    }
}
