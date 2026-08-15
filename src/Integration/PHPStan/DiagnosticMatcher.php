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
 * @logion [SFA 64:32] The examiner counted both processions before comparing words, then exchanged tentative cords
 *     until every promise had one answer or the impossibility of such a judgment stood proven.
 */
final class DiagnosticMatcher
{
    /**
     * @param list<DiagnosticExpectation> $expectations
     * @param list<AnalyzerDiagnostic> $diagnostics
     *
     * @logion [OSD 64:33] Refuse unequal counts at once; otherwise seek a complete one-to-one appointment by exact
     *     case-sensitive phrase, consulting sentence and counsel together without consuming either greedily.
     */
    public function match(array $expectations, array $diagnostics): DiagnosticMatchResult
    {
        $expectations = DiagnosticListValidator::expectations($expectations);
        $diagnostics = DiagnosticListValidator::diagnostics($diagnostics);

        if (count($expectations) !== count($diagnostics)) {
            return new DiagnosticsMismatched(DiagnosticMismatchKind::Count, $expectations, $diagnostics);
        }

        $compatibleDiagnostics = [];
        foreach ($expectations as $expectation) {
            $compatible = [];
            foreach ($diagnostics as $diagnosticIndex => $diagnostic) {
                $identifierMatches = $expectation->identifier === null
                    || $diagnostic->identifier === $expectation->identifier;
                $textMatches = $expectation->text === null
                    || str_contains($diagnostic->searchableText(), $expectation->text);
                $diagnosticLine = $diagnostic->sourceLine ?? $diagnostic->analyzerLine;
                $lineMatches = $expectation->sourceLineRange === null
                    || ($diagnosticLine !== null
                        && $diagnosticLine >= $expectation->sourceLineRange['first']
                        && $diagnosticLine <= $expectation->sourceLineRange['last']);
                if ($identifierMatches && $textMatches && $lineMatches) {
                    $compatible[] = $diagnosticIndex;
                }
            }
            $compatibleDiagnostics[] = $compatible;
        }

        $diagnosticToExpectation = array_fill(0, count($diagnostics), null);
        foreach (array_keys($expectations) as $expectationIndex) {
            $visitedDiagnostics = array_fill(0, count($diagnostics), false);
            $assignment = self::assignExpectation(
                $expectationIndex,
                $compatibleDiagnostics,
                $visitedDiagnostics,
                $diagnosticToExpectation,
            );
            if (!$assignment['matched']) {
                return new DiagnosticsMismatched(DiagnosticMismatchKind::Assignment, $expectations, $diagnostics);
            }
            $diagnosticToExpectation = $assignment['assignments'];
        }

        $expectationToDiagnostic = [];
        foreach ($diagnosticToExpectation as $diagnosticIndex => $expectationIndex) {
            if ($expectationIndex === null) {
                throw new \LogicException('A complete diagnostic assignment left a diagnostic unassigned.');
            }
            $expectationToDiagnostic[$expectationIndex] = $diagnosticIndex;
        }
        ksort($expectationToDiagnostic);

        $assignments = [];
        foreach ($expectationToDiagnostic as $expectationIndex => $diagnosticIndex) {
            $assignments[] = new DiagnosticAssignment(
                $expectations[$expectationIndex],
                $diagnostics[$diagnosticIndex],
            );
        }

        return new DiagnosticsMatched($assignments);
    }

    /**
     * @param list<list<int>> $compatibleDiagnostics
     * @param list<bool> $visitedDiagnostics
     * @param array<int, int|null> $diagnosticToExpectation
     *
     * @return array{matched: bool, visited: list<bool>, assignments: array<int, int|null>}
     *
     * @logion [SFA 64:36] When one answer was already corded, the clerk followed its former promise and sought another
     *     lawful answer for it, moving cords without ever assigning one judgment twice.
     */
    private static function assignExpectation(
        int $expectationIndex,
        array $compatibleDiagnostics,
        array $visitedDiagnostics,
        array $diagnosticToExpectation,
    ): array {
        foreach ($compatibleDiagnostics[$expectationIndex] as $diagnosticIndex) {
            if ($visitedDiagnostics[$diagnosticIndex]) {
                continue;
            }
            $visitedDiagnostics[$diagnosticIndex] = true;

            $previousExpectation = $diagnosticToExpectation[$diagnosticIndex];
            if ($previousExpectation !== null) {
                $assignment = self::assignExpectation(
                    $previousExpectation,
                    $compatibleDiagnostics,
                    $visitedDiagnostics,
                    $diagnosticToExpectation,
                );
                $visitedDiagnostics = $assignment['visited'];
                $diagnosticToExpectation = $assignment['assignments'];
                if (!$assignment['matched']) {
                    // Retain failed branches' visited marks so sibling searches do not revisit the same subtree.
                    continue;
                }
            }

            $diagnosticToExpectation[$diagnosticIndex] = $expectationIndex;

            return [
                'matched' => true,
                'visited' => $visitedDiagnostics,
                'assignments' => $diagnosticToExpectation,
            ];
        }

        return [
            'matched' => false,
            'visited' => $visitedDiagnostics,
            'assignments' => $diagnosticToExpectation,
        ];
    }
}
