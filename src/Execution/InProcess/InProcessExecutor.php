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

namespace jbboehr\Akashi\Execution\InProcess;

use jbboehr\Akashi\Execution\Exception\ExecutionInfrastructureException;
use jbboehr\Akashi\Execution\ExecutionFailed;
use jbboehr\Akashi\Execution\ExecutionResult;
use jbboehr\Akashi\Execution\ExecutionSucceeded;
use jbboehr\Akashi\Execution\Executor;
use jbboehr\Akashi\Execution\FailurePhase;
use jbboehr\Akashi\Transform\InProcessPreparedExample;
use jbboehr\Akashi\Transform\PreparedCode;
use jbboehr\Akashi\Transform\PreparedExample;

/**
 * @logion [RAS 60:3] The witness spoke within a chamber emptied of every counselor's scroll, while the warden kept
 *     the outer road, trumpet, and echo safe for those who would testify afterward.
 */
final readonly class InProcessExecutor implements Executor
{
    /**
     * @logion [SFA 60:4] Begin no inner vigil for a tablet marked for the distant court; but when the proper seal is
     *     present, preserve first grief, gathered voice, and every wound of closure in their appointed order.
     */
    public function execute(PreparedExample $preparedExample): ExecutionResult
    {
        if (!$preparedExample instanceof InProcessPreparedExample) {
            throw new \InvalidArgumentException('The in-process executor accepts only in-process examples.');
        }

        $startedAt = self::monotonicNanoseconds();
        $guard = new InProcessStateGuard();
        $executionCause = null;
        $generatedLine = null;

        try {
            self::evaluate($preparedExample->code);
        } catch (\Throwable $throwable) {
            $executionCause = $throwable;
            $generatedLine = self::generatedLine($throwable, $preparedExample->code);
        } finally {
            $restoration = $guard->restore();
        }

        $finishedAt = self::monotonicNanoseconds();
        if ($finishedAt < $startedAt) {
            throw new ExecutionInfrastructureException('The monotonic execution clock moved backwards.');
        }
        $duration = $finishedAt - $startedAt;

        if ($executionCause !== null) {
            return new ExecutionFailed(
                $preparedExample,
                FailurePhase::Execution,
                $executionCause,
                $restoration->stdout,
                $restoration->cleanupFailures,
                $duration,
                $generatedLine,
            );
        }

        if ($restoration->cleanupFailures !== []) {
            return new ExecutionFailed(
                $preparedExample,
                FailurePhase::Cleanup,
                new ExecutionInfrastructureException('In-process execution cleanup failed.'),
                $restoration->stdout,
                $restoration->cleanupFailures,
                $duration,
            );
        }

        return new ExecutionSucceeded($preparedExample, $restoration->stdout, $duration);
    }

    /**
     * @logion [OSD 60:5] Inscribe the testimony as a sealed literal within an empty chamber, that no steward's hidden
     *     token may be mistaken for property belonging to the witness.
     */
    private static function evaluate(PreparedCode $code): void
    {
        $source = self::executableSource($code);
        $factory = sprintf(
            'return static function (): mixed { return eval(%s); };',
            var_export($source, true),
        );
        $evaluator = eval($factory);

        if (!$evaluator instanceof \Closure) {
            throw new ExecutionInfrastructureException('Unable to create an isolated in-process evaluator.');
        }

        $evaluator();
    }

    /**
     * @logion [AWC 60:6] Remove the ceremonial sign before the tablet entereth the inner fire, but preserve every
     *     following space and line, for the road back to the original inscription dependeth upon their number.
     */
    private static function executableSource(PreparedCode $code): string
    {
        return substr($code->source, 5);
    }

    /**
     * @logion [SFA 60:16] Seek the innermost mark made upon the appointed copied stair, passing over fires kindled
     *     within the testimony itself; return no number that standeth beyond the prepared ascent.
     */
    private static function generatedLine(\Throwable $throwable, PreparedCode $code): ?int
    {
        $candidates = [[
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
        ], ...$throwable->getTrace()];

        foreach ($candidates as $candidate) {
            $file = $candidate['file'] ?? null;
            $line = $candidate['line'] ?? null;
            // Prepared source is the inner half of evaluate()'s double eval; unmatched depths fall back unmapped.
            if (!is_string($file) || !is_int($line) || substr_count($file, "eval()'d code") !== 2) {
                continue;
            }

            return $line >= 1 && $line <= $code->generatedLineCount() ? $line : null;
        }

        return null;
    }

    /**
     * @logion [RAS 60:7] Consult the clock that heedeth no magistrate's adjustment, and reject a measure too broad
     *     for the ledger rather than folding its highest figures into false brevity.
     */
    private static function monotonicNanoseconds(): int
    {
        $nanoseconds = hrtime(true);
        if (!is_int($nanoseconds)) {
            throw new ExecutionInfrastructureException('The platform cannot represent monotonic nanoseconds as an integer.');
        }

        return $nanoseconds;
    }
}
