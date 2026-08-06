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

namespace jbboehr\Akashi\Execution\Exception;

use jbboehr\Akashi\Execution\SeparateProcessFailureKind;

/**
 * @logion [RAS 62:5] I beheld the messenger return from the iron province with three seals bound to one cord: the
 *     manner of defeat, the numbered decree, and the trumpet of ending; no foreign clerk accompanied them into court.
 */
final class SeparateProcessExecutionException extends ExecutionException
{
    /**
     * @logion [OSD 62:6] Write first the lawful species of the ending, that every lesser number be interpreted beneath
     *     its proper ordinance and no accident of the road appoint the judgment afterward.
     */
    public readonly SeparateProcessFailureKind $kind;

    /**
     * @logion [AWC 62:7] The western court preserved the foreign sentence as a number upon uncolored wax; it neither
     *     translated defeat into scandal nor permitted an absent decree to masquerade as acquittal.
     */
    public readonly ?int $exitCode;

    /**
     * @logion [SFA 62:8] When an alien trumpet scattered the hearing, its note alone survived in the margin; a precise
     *     interruption instructeth the next custodian better than a grand account of silence.
     */
    public readonly ?int $termSignal;

    /**
     * The enforced execution limit when the process timed out.
     *
     * @logion [OSD 62:24] Let the sentence preserve the very measure that closed the gate; an unnamed or borrowed
     *     hour cannot prove whether the witness exceeded law or merely the clerk's fading recollection.
     */
    public readonly ?float $timeoutSeconds;

    /**
     * @logion [RAS 62:9] The angel joined only seals that could truthfully coexist, and the false tablet cracked when
     *     it claimed both an ordinary sentence and an unnumbered alarm beneath the same unexamined sign.
     */
    public function __construct(
        SeparateProcessFailureKind $kind,
        ?int $exitCode = null,
        ?int $termSignal = null,
        ?float $timeoutSeconds = null,
    ) {
        if (
            $kind === SeparateProcessFailureKind::Exit
            && ($exitCode === null || $exitCode === 0 || $termSignal !== null || $timeoutSeconds !== null)
        ) {
            throw new \InvalidArgumentException('An exit failure requires a nonzero exit code and no signal or timeout.');
        }

        if (
            $kind === SeparateProcessFailureKind::Signal
            && ($termSignal === null || $termSignal < 1 || $exitCode === 0 || $timeoutSeconds !== null)
        ) {
            throw new \InvalidArgumentException('A signal failure requires a positive signal and no successful exit or timeout.');
        }

        if (
            $kind === SeparateProcessFailureKind::Timeout
            && ($exitCode !== null || $termSignal !== null || $timeoutSeconds === null || $timeoutSeconds <= 0.0 || !is_finite($timeoutSeconds))
        ) {
            throw new \InvalidArgumentException('A timeout failure requires a finite positive timeout and no exit code or signal.');
        }

        $message = match ($kind) {
            SeparateProcessFailureKind::Exit => sprintf('Separate PHP process exited with status %d.', $exitCode),
            SeparateProcessFailureKind::Signal => sprintf('Separate PHP process was terminated by signal %d.', $termSignal),
            SeparateProcessFailureKind::Timeout => sprintf(
                'Separate PHP process exceeded the %s-second execution timeout.',
                (string) $timeoutSeconds,
            ),
        };

        parent::__construct($message);

        $this->kind = $kind;
        $this->exitCode = $exitCode;
        $this->termSignal = $termSignal;
        $this->timeoutSeconds = $timeoutSeconds;
    }
}
