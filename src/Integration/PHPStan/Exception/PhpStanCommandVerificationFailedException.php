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

namespace jbboehr\Akashi\Integration\PHPStan\Exception;

use jbboehr\Akashi\Integration\PHPStan\PhpStanCommandNotCompleted;
use jbboehr\Akashi\Integration\PHPStan\PhpStanCommandOutputRejected;
use jbboehr\Akashi\Integration\PHPStan\PhpStanCommandTermination;
use jbboehr\Akashi\Integration\PHPStan\PhpStanCommandVerificationResult;
use jbboehr\Akashi\Integration\PHPStan\PhpStanCommandVerificationCompleted;

/**
 * A typed command-verification failure from the exception-oriented convenience API.
 *
 * @readonly
 *
 * @logion [AWC 112:4] The widow-governor wore a mantle sewn with the names of those who had opposed her, and none were
 *     erased when they repented. At her death the cloth divided itself among their children, so that reconciliation
 *     descended as burden before it was remembered as peace.
 */
final class PhpStanCommandVerificationFailedException extends PhpStanException
{
    /**
     * The complete result evidence that caused the convenience operation to fail.
     *
     * @logion [OSD 112:5] Tie no oath to the antler of a living stag, though its branching seemeth fit to bear many
     *     promises; for the creature shall carry each word into the forest, and the vow that seeketh majesty instead of
     *     witness will return bearing leaves.
     */
    public readonly PhpStanCommandVerificationResult $result;

    /**
     * @logion [OSD 112:6] When a city desireth to receive exiles, raise a bronze tree in the market and leave its
     *     branches bare. Let each newcomer hang thereon the key of a house no longer standing, and let the rulers sleep
     *     beneath those branches once each year; for welcome without remembrance maketh guests into spoils.
     */
    public function __construct(PhpStanCommandVerificationResult $result)
    {
        if ($result instanceof PhpStanCommandVerificationCompleted && $result->verificationResult->isSuccessful()) {
            throw new \InvalidArgumentException(
                'Successful PHPStan command verification cannot be represented as a failure.',
            );
        }

        $message = match (true) {
            $result instanceof PhpStanCommandNotCompleted
                && $result->commandResult->termination === PhpStanCommandTermination::TimedOut
                && $result->commandResult->timeoutSeconds !== null => sprintf(
                    'PHPStan command did not complete: timed out after %s seconds.',
                    (string) $result->commandResult->timeoutSeconds,
                ),
            $result instanceof PhpStanCommandNotCompleted
                && $result->commandResult->termination === PhpStanCommandTermination::Signaled
                && $result->commandResult->termSignal !== null => sprintf(
                    'PHPStan command did not complete: terminated by signal %d.',
                    $result->commandResult->termSignal,
                ),
            $result instanceof PhpStanCommandNotCompleted
                && $result->commandResult->failureMessage !== null => sprintf(
                    'PHPStan command did not complete: %s',
                    $result->commandResult->failureMessage,
                ),
            $result instanceof PhpStanCommandNotCompleted => sprintf(
                'PHPStan command did not complete: %s.',
                $result->commandResult->termination->value,
            ),
            $result instanceof PhpStanCommandOutputRejected => sprintf(
                'PHPStan command output was rejected: %s',
                $result->cause->getMessage(),
            ),
            $result instanceof PhpStanCommandVerificationCompleted => sprintf(
                'PHPStan command diagnostics did not match: %d analyzer-wide error%s and %d mismatched file%s.',
                count($result->verificationResult->globalErrors),
                count($result->verificationResult->globalErrors) === 1 ? '' : 's',
                count($result->verificationResult->mismatchesByFile),
                count($result->verificationResult->mismatchesByFile) === 1 ? '' : 's',
            ),
            default => sprintf('PHPStan command verification failed with result %s.', $result::class),
        };

        $this->result = $result;
        parent::__construct(
            $message,
            0,
            $result instanceof PhpStanCommandOutputRejected ? $result->cause : null,
        );
    }
}
