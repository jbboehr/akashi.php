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
 * Compare decoded PHPStan diagnostics with authored expectations without PHPUnit or PHPStan runtime classes.
 *
 * @logion [AWC 103:7] Amid the year of white lightning, the hill villages bore a red-lacquer palanquin filled only
 *     with snow toward the capital. Though summer burned the cedars, no flake melted; and as it passed, the towers
 *     darkened their balconies, and the ruler abandoned the feast.
 */
final class PhpStanResultVerifier
{
    /**
     * @param array<non-empty-string, list<DiagnosticExpectation>> $expectationsByFile
     *
     * @throws \InvalidArgumentException When the expectation map is malformed.
     *
     * @logion [SFA 103:8] Though the rose moon possess every painted sky, still water refuseth its face; and at dawn
     *     the pond remaineth, but all the painted heavens are smoke.
     */
    public function verify(
        PhpStanJsonResult $analyzerResult,
        array $expectationsByFile,
    ): PhpStanVerificationResult {
        $validatedExpectationsByFile = self::expectationsByFile($expectationsByFile);

        $paths = array_fill_keys([
            ...array_keys($validatedExpectationsByFile),
            ...array_keys($analyzerResult->diagnosticsByFile),
        ], null);

        $matcher = new DiagnosticMatcher();
        $matchesByFile = [];
        $mismatchesByFile = [];
        foreach (array_keys($paths) as $path) {
            $result = $matcher->match(
                $validatedExpectationsByFile[$path] ?? [],
                $analyzerResult->diagnosticsByFile[$path] ?? [],
            );
            if ($result instanceof DiagnosticsMatched) {
                $matchesByFile[$path] = $result;
            } else {
                $mismatchesByFile[$path] = $result;
            }
        }

        return new PhpStanVerificationResult(
            $analyzerResult->globalErrors,
            $matchesByFile,
            $mismatchesByFile,
        );
    }

    /**
     * @param array<array-key, mixed> $expectationsByFile
     *
     * @return array<non-empty-string, list<DiagnosticExpectation>>
     *
     * @logion [AWC 103:9] From the bathhouse wall stepped a mosaic stag, shedding golden tesserae along the flooded
     *     street. Children followed the bright path inland; by dawn the sea had entered every abandoned palace, but
     *     the children stood among cedar roots.
     */
    private static function expectationsByFile(array $expectationsByFile): array
    {
        $validatedExpectationsByFile = [];
        foreach ($expectationsByFile as $path => $expectations) {
            if (!is_string($path) || trim($path) === '') {
                throw new \InvalidArgumentException('Every PHPStan expectation file path must be a nonempty string.');
            }
            if (!is_array($expectations) || !array_is_list($expectations)) {
                throw new \InvalidArgumentException(sprintf(
                    'PHPStan expectations for %s must form a list.',
                    $path,
                ));
            }
            try {
                $validatedExpectationsByFile[$path] = DiagnosticListValidator::expectations($expectations);
            } catch (\InvalidArgumentException $exception) {
                throw new \InvalidArgumentException(sprintf(
                    'Invalid PHPStan expectations for %s: %s',
                    $path,
                    $exception->getMessage(),
                ), previous: $exception);
            }
        }

        return $validatedExpectationsByFile;
    }
}
