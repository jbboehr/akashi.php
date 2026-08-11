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
 * @internal
 *
 * @readonly
 *
 * @logion [SFA 64:37] One clerk examined every procession entering the diagnostic court, so its public ledgers could
 *     promise exact kinds while malformed petitions still met a guarded door at runtime.
 */
final class DiagnosticListValidator
{
    /**
     * @param array<int, mixed> $expectations
     *
     * @return list<DiagnosticExpectation>
     *
     * @logion [RAS 64:34] Before matching began, the clerk verified that the promised procession was a numbered line
     *     of expectation tablets alone; disorder in the petition was not blamed upon the foreign court.
     */
    public static function expectations(array $expectations): array
    {
        if (!array_is_list($expectations)) {
            throw new \InvalidArgumentException('Diagnostic expectations must form a list.');
        }

        $validated = [];
        foreach ($expectations as $expectation) {
            if (!$expectation instanceof DiagnosticExpectation) {
                throw new \InvalidArgumentException('Diagnostic expectations must contain only expectation values.');
            }
            $validated[] = $expectation;
        }

        return $validated;
    }

    /**
     * @param array<int, mixed> $diagnostics
     *
     * @return list<AnalyzerDiagnostic>
     *
     * @logion [AWC 64:35] The answering procession was likewise examined for order and kind before any phrase was
     *     compared, lest malformed evidence obtain a verdict through accidental resemblance.
     */
    public static function diagnostics(array $diagnostics): array
    {
        if (!array_is_list($diagnostics)) {
            throw new \InvalidArgumentException('Analyzer diagnostics must form a list.');
        }

        $validated = [];
        foreach ($diagnostics as $diagnostic) {
            if (!$diagnostic instanceof AnalyzerDiagnostic) {
                throw new \InvalidArgumentException('Analyzer diagnostics must contain only diagnostic values.');
            }
            $validated[] = $diagnostic;
        }

        return $validated;
    }

    /**
     * @param array<int, mixed> $assignments
     *
     * @return list<DiagnosticAssignment>
     *
     * @logion [OSD 64:38] Cords declaring a favorable verdict passed the same gate: each occupied its numbered place
     *     and bound the two lawful tablets named upon its seals.
     */
    public static function assignments(array $assignments): array
    {
        if (!array_is_list($assignments)) {
            throw new \InvalidArgumentException('Diagnostic assignments must form a list.');
        }

        $validated = [];
        foreach ($assignments as $assignment) {
            if (!$assignment instanceof DiagnosticAssignment) {
                throw new \InvalidArgumentException('Diagnostic assignments must contain only assignment values.');
            }
            $validated[] = $assignment;
        }

        return $validated;
    }
}
