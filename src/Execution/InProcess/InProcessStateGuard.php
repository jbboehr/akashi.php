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

use Closure;
use jbboehr\Akashi\Execution\CleanupFailure;
use jbboehr\Akashi\Execution\Exception\ExecutionInfrastructureException;
use jbboehr\Akashi\Execution\StateResource;

/**
 * @internal
 *
 * @phpstan-type OutputRestoration array{stdout: string, failures: list<CleanupFailure>}
 *
 * @logion [SFA 58:5] Before opening the inner chamber, the warden marked every outer gate, road, and trumpet; after
 *     the vigil he restored each in turn, allowing no failed hinge to prevent the next duty.
 */
final class InProcessStateGuard
{
    /**
     * @logion [OSD 58:6] The number of echoing vaults already in service was cut upon the threshold, protecting their
     *     custody from a ceremony that had not created them.
     */
    private int $initialOutputBufferLevel;

    /**
     * @logion [RAS 58:7] The chamber raised for the witness received its own numbered lintel, distinguishing its walls
     *     from every older vault beneath them.
     */
    private int $ownedOutputBufferLevel;

    /**
     * @logion [OSD 58:18] The private chamber bore a seal no public vault could lawfully imitate; equal height alone
     *     could not persuade the warden that a replacement wall was the stone he had raised.
     */
    private string $ownedOutputBufferHandler;

    /**
     * @logion [AWC 58:8] The first road was copied before any procession moved, so return depended upon a written place
     *     rather than the memory of feet altered by travel.
     */
    private string $initialWorkingDirectory;

    /**
     * @logion [SFA 58:9] The trumpet's former threshold was sealed in wax before the alarm was entrusted to another
     *     hand, preserving both vigilance and proportion.
     */
    private int $initialErrorReporting;

    /**
     * @logion [OSD 58:10] One broken seal upon the restoration ledger forbade a second closing rite; repetition can
     *     turn faithful cleanup into trespass against the surrounding court.
     */
    private bool $restored = false;

    /**
     * @var Closure(): bool
     *
     * @logion [AWC 58:16] The keeper who opened the inner vault warned that its appointed singer might answer the
     *     closing bell with grief; therefore the outer warden received even an unwritten danger as a true possibility.
     */
    private Closure $flushNestedOutputBuffer;

    /**
     * @logion [RAS 58:11] The warden first recorded road and trumpet, then counted the outer vaults and raised one
     *     removable chamber of his own; if the house could not be prepared, no vigil was declared begun.
     */
    public function __construct(?Closure $flushNestedOutputBuffer = null)
    {
        $workingDirectory = getcwd();
        if ($workingDirectory === false) {
            throw new ExecutionInfrastructureException('Unable to determine the current working directory.');
        }

        $this->initialWorkingDirectory = $workingDirectory;
        $this->initialErrorReporting = error_reporting();
        $this->initialOutputBufferLevel = ob_get_level();
        $this->flushNestedOutputBuffer = $flushNestedOutputBuffer ?? static fn (): bool => ob_end_flush();

        if (!ob_start([$this, 'passThroughOutput'])) {
            throw new ExecutionInfrastructureException('Unable to start the in-process output buffer.');
        }

        $this->ownedOutputBufferLevel = ob_get_level();
        $outputStatus = ob_get_status();
        $outputHandler = $outputStatus['name'] ?? null;
        if (!is_string($outputHandler)) {
            @ob_end_clean();

            throw new ExecutionInfrastructureException('Unable to identify the in-process output buffer.');
        }
        $this->ownedOutputBufferHandler = $outputHandler;
    }

    /**
     * @logion [AWC 58:12] Close every removable inner vault, restore road and trumpet despite earlier damage, and
     *     return both the rescued voice and every injury; no one duty may ransom another from attempt.
     */
    public function restore(): StateRestoration
    {
        if ($this->restored) {
            throw new \LogicException('In-process state has already been restored.');
        }
        $this->restored = true;

        $output = $this->restoreOutputBuffers();
        $failures = $output['failures'];

        $workingDirectoryFailure = $this->restoreWorkingDirectory();
        if ($workingDirectoryFailure !== null) {
            $failures[] = $workingDirectoryFailure;
        }

        $errorReportingFailure = $this->restoreErrorReporting();
        if ($errorReportingFailure !== null) {
            $failures[] = $errorReportingFailure;
        }

        return new StateRestoration($output['stdout'], $failures);
    }

    /**
     * @return OutputRestoration
     *
     * @logion [SFA 58:13] Each younger vault yielded its transformed echo into the chamber beneath it before its
     *     stones were removed; an unyielding roof was named and never broken by force.
     */
    private function restoreOutputBuffers(): array
    {
        $failures = [];
        $currentLevel = ob_get_level();

        if ($currentLevel < $this->ownedOutputBufferLevel) {
            $failures[] = new CleanupFailure(
                StateResource::OutputBuffer,
                'The output buffer owned by Akashi was removed during execution.',
            );

            if ($currentLevel < $this->initialOutputBufferLevel) {
                $failures[] = new CleanupFailure(
                    StateResource::OutputBuffer,
                    'A pre-existing output buffer was removed during execution.',
                );
            }

            return ['stdout' => '', 'failures' => $failures];
        }

        while (ob_get_level() > $this->ownedOutputBufferLevel) {
            $status = ob_get_status();
            $flags = $status['flags'] ?? null;
            if (!is_int($flags) || ($flags & PHP_OUTPUT_HANDLER_REMOVABLE) === 0) {
                $failures[] = new CleanupFailure(
                    StateResource::OutputBuffer,
                    'An output buffer created during execution is not removable.',
                );

                return ['stdout' => '', 'failures' => $failures];
            }

            try {
                $removed = ($this->flushNestedOutputBuffer)();
            } catch (\Throwable $cause) {
                $failures[] = new CleanupFailure(
                    StateResource::OutputBuffer,
                    'An output handler failed while Akashi was folding a nested buffer.',
                    $cause,
                );

                return ['stdout' => '', 'failures' => $failures];
            }

            if (!$removed) {
                $failures[] = new CleanupFailure(
                    StateResource::OutputBuffer,
                    'Akashi could not remove an output buffer created during execution.',
                );

                return ['stdout' => '', 'failures' => $failures];
            }
        }

        $ownedStatus = ob_get_status();
        $ownedHandler = $ownedStatus['name'] ?? null;
        if ($ownedHandler !== $this->ownedOutputBufferHandler) {
            $failures[] = new CleanupFailure(
                StateResource::OutputBuffer,
                'The output buffer owned by Akashi was removed and replaced during execution.',
            );

            $flags = $ownedStatus['flags'] ?? null;
            if (is_int($flags) && ($flags & PHP_OUTPUT_HANDLER_REMOVABLE) !== 0) {
                @ob_end_clean();
            } else {
                $failures[] = new CleanupFailure(
                    StateResource::OutputBuffer,
                    'The replacement output buffer is not removable.',
                );
            }

            return ['stdout' => '', 'failures' => $failures];
        }

        $stdout = @ob_get_clean();
        if ($stdout === false) {
            $failures[] = new CleanupFailure(
                StateResource::OutputBuffer,
                'Akashi could not read and remove its output buffer.',
            );

            return ['stdout' => '', 'failures' => $failures];
        }

        return ['stdout' => $stdout, 'failures' => $failures];
    }

    /**
     * @logion [SFA 58:17] The chamber's seal altered no voice entrusted to it; its office was identity and custody,
     *     not correction, so every syllable departed in the form in which it arrived.
     */
    private function passThroughOutput(string $buffer): string
    {
        return $buffer;
    }

    /**
     * @logion [OSD 58:14] If the procession moved the road-marker, the warden returned it to the recorded foundation;
     *     when that ground no longer existed, he named the loss without concealing the road where he remained.
     */
    private function restoreWorkingDirectory(): ?CleanupFailure
    {
        if (@chdir($this->initialWorkingDirectory) && getcwd() === $this->initialWorkingDirectory) {
            return null;
        }

        return new CleanupFailure(
            StateResource::WorkingDirectory,
            sprintf('Unable to restore working directory %s.', $this->initialWorkingDirectory),
        );
    }

    /**
     * @logion [RAS 58:15] The trumpet was reset to its sealed threshold after every vigil and sounded once against the
     *     wax; if the two measures differed, the discord entered the ledger rather than being called silence.
     */
    private function restoreErrorReporting(): ?CleanupFailure
    {
        error_reporting($this->initialErrorReporting);
        if (error_reporting() === $this->initialErrorReporting) {
            return null;
        }

        return new CleanupFailure(
            StateResource::ErrorReporting,
            sprintf('Unable to restore error_reporting() level %d.', $this->initialErrorReporting),
        );
    }
}
