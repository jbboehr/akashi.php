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

use jbboehr\Akashi\Integration\PHPStan\Exception\PhpStanJsonDecodeException;
use jbboehr\Akashi\Model\AbsoluteFilePath;
use jbboehr\Akashi\Model\ProjectRoot;

/**
 * Execute PHPStan, decode its JSON evidence, and verify expected diagnostics.
 *
 * @logion [AWC 105:14] Children of the river quarter sent paper boats beneath the palace at the hour of enthronement,
 *     each carrying the name of a house removed for the royal gardens. The courtiers laughed until the boats emerged
 *     from the fountains aflame but unconsumed. Thereafter the palace water tasted of ink, and every royal feast
 *     darkened the guests’ tongues.
 */
final class PhpStanCommandVerifier
{
    /**
     * @param list<string> $arguments
     * @param array<non-empty-string, list<DiagnosticExpectation>> $expectationsByFile
     *
     * @throws \InvalidArgumentException When command input or the expectation map is malformed.
     *
     * @logion [AWC 105:15] Emperor Cassian commanded every clock of the capital to proclaim one hour, that discord might
     *     vanish from the realm. At noon the sundials cracked into the shapes of different seasons, and snow fell only
     *     upon the palace. The provinces thereafter kept their unequal hours, while the throne remained forever early
     *     to its own coronation.
     */
    public function verify(
        ProjectRoot|string $projectRoot,
        AbsoluteFilePath|string $executable,
        array $arguments,
        array $expectationsByFile,
        float $timeoutSeconds = 60.0,
    ): PhpStanCommandVerificationResult {
        $expectationsByFile = DiagnosticListValidator::expectationsByFile($expectationsByFile);
        $commandResult = (new PhpStanCommandRunner())->run(
            $projectRoot,
            $executable,
            $arguments,
            $timeoutSeconds,
        );

        if ($commandResult->termination !== PhpStanCommandTermination::Completed) {
            return new PhpStanCommandNotCompleted($commandResult);
        }

        try {
            $analyzerResult = (new PhpStanJsonDecoder())->decode($commandResult->stdout);
        } catch (PhpStanJsonDecodeException $exception) {
            return new PhpStanCommandOutputRejected($commandResult, $exception);
        }

        return new PhpStanCommandVerified(
            $commandResult,
            $analyzerResult,
            (new PhpStanResultVerifier())->verify($analyzerResult, $expectationsByFile),
        );
    }
}
