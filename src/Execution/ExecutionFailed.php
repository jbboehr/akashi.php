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
 * @logion [AWC 57:7] The extinguished lantern was not cast into the ravine; its route, final smoke, and every broken
 *     clasp were borne home together so the darkness could be judged without invention.
 */
final class ExecutionFailed implements ExecutionResult
{
    /**
     * @logion [SFA 57:8] Though the testimony faltered, the prepared tablet remained beside the verdict, preserving
     *     the exact chamber and inscription against which failure had contended.
     */
    public readonly PreparedExample $preparedExample;

    /**
     * @logion [OSD 57:9] The hour of failure was carved upon the lintel, distinguishing a rite that broke in its
     *     speaking from a sanctuary that broke while restoring silence.
     */
    public readonly FailurePhase $phase;

    /**
     * @logion [RAS 57:10] The first wound kept precedence in the physician's account, even when lesser cuts appeared
     *     during washing; chronology was not permitted to flatter the final instrument.
     */
    public readonly \Throwable $cause;

    /**
     * @logion [AWC 57:11] The interrupted voice was gathered to its final syllable, for a broken proclamation may
     *     still contain the word that revealeth why the herald fell.
     */
    public readonly string $stdout;

    /**
     * Bytes written to the example's standard error stream before failure.
     *
     * @logion [RAS 62:21] I saw two rivers descend from the condemned city, one bearing petitions and the other
     *     alarms; the judge received both waters unmixed, and from their difference discerned where the breach began.
     */
    public readonly string $stderr;

    /**
     * @var list<CleanupFailure>
     *
     * @logion [SFA 57:12] Every fault discovered while closing the chamber stood in ordered procession after the first
     *     grief; none could depose it, and none could vanish behind it.
     */
    public readonly array $cleanupFailures;

    /**
     * @logion [OSD 57:13] Even the failed voyage retained an honest count of its heartbeats, that haste and delay might
     *     be known without pretending either caused the storm.
     */
    public readonly int $durationNanoseconds;

    /**
     * @var positive-int|null
     *
     * @logion [RAS 60:15] Mark the step upon the copied stair where the traveler fell, but leave the tablet blank
     *     when smoke concealeth the number; an honest absence guideth the surveyor better than a guessed ascent.
     */
    public readonly ?int $generatedLine;

    /**
     * @param array<int, mixed> $cleanupFailures
     *
     * @logion [RAS 57:14] The notary joined cause, hour, voice, duration, and the ordered injuries of closure; if
     *     cleanup alone condemned the rite, at least one such injury was required to stand before the seal.
     */
    public function __construct(
        PreparedExample $preparedExample,
        FailurePhase $phase,
        \Throwable $cause,
        string $stdout,
        array $cleanupFailures,
        int $durationNanoseconds,
        ?int $generatedLine = null,
        string $stderr = '',
    ) {
        if (!array_is_list($cleanupFailures)) {
            throw new \InvalidArgumentException('Cleanup failures must form a list.');
        }

        foreach ($cleanupFailures as $cleanupFailure) {
            if (!$cleanupFailure instanceof CleanupFailure) {
                throw new \InvalidArgumentException('Cleanup failures must contain only cleanup failure values.');
            }
        }

        if ($phase === FailurePhase::Cleanup && $cleanupFailures === []) {
            throw new \InvalidArgumentException('A cleanup-phase failure must contain at least one cleanup failure.');
        }

        if ($durationNanoseconds < 0) {
            throw new \InvalidArgumentException('Execution duration must not be negative.');
        }

        if (
            $generatedLine !== null
            && ($generatedLine < 1 || $generatedLine > $preparedExample->sourceMap->generatedLineCount())
        ) {
            throw new \InvalidArgumentException('Generated failure line must exist in the prepared source.');
        }

        $this->preparedExample = $preparedExample;
        $this->phase = $phase;
        $this->cause = $cause;
        $this->stdout = $stdout;
        $this->stderr = $stderr;
        $this->cleanupFailures = $cleanupFailures;
        $this->durationNanoseconds = $durationNanoseconds;
        $this->generatedLine = $generatedLine;
    }
}
