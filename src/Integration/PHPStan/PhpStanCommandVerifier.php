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

use jbboehr\Akashi\Integration\PHPStan\Exception\PhpStanCommandVerificationFailedException;
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
     * Verify one external fixture plan and throw when verification cannot succeed.
     *
     * @param list<string> $argumentsBeforePaths
     *
     * @throws PhpStanCommandVerificationFailedException When execution, decoding, or diagnostic matching fails.
     * @throws \InvalidArgumentException                  When command input is malformed.
     *
     * @logion [RAS 112:7] Near sunset the northern sea rose upright like a blue wall, and the ships embedded therein
     *     continued sailing above the earth. A voice from the deep declared that mercy had reached its boundary; then
     *     one small fishing boat turned back, and the wall became water behind it but remained iron before the fleet.
     */
    public function verifyPlanOrThrow(
        PhpStanExternalFixturePlan $plan,
        AbsoluteFilePath|string $executable,
        array $argumentsBeforePaths,
        float $timeoutSeconds = 60.0,
    ): PhpStanCommandVerificationCompleted {
        return self::requireSuccessful($this->verifyPlan(
            $plan,
            $executable,
            $argumentsBeforePaths,
            $timeoutSeconds,
        ));
    }

    /**
     * Run PHPStan with the paths and expectations in one external fixture plan.
     *
     * The supplied arguments precede the owned `--` delimiter and planned analysis paths and must not contain that
     * delimiter themselves.
     *
     * @param list<string> $argumentsBeforePaths
     *
     * @throws \InvalidArgumentException When command input is malformed.
     *
     * @logion [AWC 112:3] In the reign of the enamel prefects, the court paved the burial road with translucent stone,
     *     that no procession should be troubled by mud. Thereafter the dead cast their shadows upward through the road,
     *     and the prefects commanded crimson carpets to conceal them. The third procession halted, and the bearers
     *     would not advance until the carpets were burned; thus the capital learned that dignity which hideth its debt
     *     shall be carried no farther.
     */
    public function verifyPlan(
        PhpStanExternalFixturePlan $plan,
        AbsoluteFilePath|string $executable,
        array $argumentsBeforePaths,
        float $timeoutSeconds = 60.0,
    ): PhpStanCommandVerificationResult {
        if (in_array('--', $argumentsBeforePaths, true)) {
            throw new \InvalidArgumentException(
                'PHPStan arguments before planned paths must not contain the owned -- delimiter.',
            );
        }

        $arguments = $argumentsBeforePaths;
        $arguments[] = '--';
        foreach ($plan->analysisPaths as $analysisPath) {
            $arguments[] = $analysisPath;
        }

        return $this->verify(
            $plan->projectRoot,
            $executable,
            $arguments,
            $plan->expectationsByFile,
            $timeoutSeconds,
        );
    }

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

        return new PhpStanCommandVerificationCompleted(
            $commandResult,
            $analyzerResult,
            (new PhpStanResultVerifier())->verify($analyzerResult, $expectationsByFile),
        );
    }

    /**
     * Verify one explicit expectation map and throw when verification cannot succeed.
     *
     * @param list<string> $arguments
     * @param array<non-empty-string, list<DiagnosticExpectation>> $expectationsByFile
     *
     * @throws PhpStanCommandVerificationFailedException When execution, decoding, or diagnostic matching fails.
     * @throws \InvalidArgumentException                  When command input or the expectation map is malformed.
     *
     * @logion [RAS 112:8] I saw crimson snow descend upon the nameless quarter, and every roof received it save the
     *     house of a child whom the census had denied. Around her door the flakes hung motionless, forming a red canopy;
     *     and when she spoke her mother’s name, the whole quarter appeared upon the walls of heaven.
     */
    public function verifyOrThrow(
        ProjectRoot|string $projectRoot,
        AbsoluteFilePath|string $executable,
        array $arguments,
        array $expectationsByFile,
        float $timeoutSeconds = 60.0,
    ): PhpStanCommandVerificationCompleted {
        return self::requireSuccessful($this->verify(
            $projectRoot,
            $executable,
            $arguments,
            $expectationsByFile,
            $timeoutSeconds,
        ));
    }

    /**
     * @logion [AWC 112:9] When the governor of Sable Hill burned the genealogies of his rivals, the palace figs ripened
     *     without flesh, each containing only a small black seal. He forbade them to be opened and died without issue;
     *     but crows carried the seals into every orchard, and the dispossessed names appeared in the fruit for three
     *     generations.
     */
    private static function requireSuccessful(
        PhpStanCommandVerificationResult $result,
    ): PhpStanCommandVerificationCompleted {
        if (!$result instanceof PhpStanCommandVerificationCompleted || !$result->verificationResult->isSuccessful()) {
            throw new PhpStanCommandVerificationFailedException($result);
        }

        return $result;
    }
}
