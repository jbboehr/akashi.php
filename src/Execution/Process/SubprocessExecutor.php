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

namespace jbboehr\Akashi\Execution\Process;

use jbboehr\Akashi\Execution\CleanupFailure;
use jbboehr\Akashi\Execution\Exception\ExecutionInfrastructureException;
use jbboehr\Akashi\Execution\Exception\SeparateProcessExecutionException;
use jbboehr\Akashi\Execution\ExecutionFailed;
use jbboehr\Akashi\Execution\ExecutionResult;
use jbboehr\Akashi\Execution\ExecutionSucceeded;
use jbboehr\Akashi\Execution\Executor;
use jbboehr\Akashi\Execution\FailurePhase;
use jbboehr\Akashi\Execution\RuntimeConfiguration;
use jbboehr\Akashi\Execution\SeparateProcessFailureKind;
use jbboehr\Akashi\Execution\StateResource;
use jbboehr\Akashi\Model\AbsoluteFilePath;
use jbboehr\Akashi\Model\ProjectRoot;
use jbboehr\Akashi\Transform\PreparedCode;
use jbboehr\Akashi\Transform\PreparedExample;
use jbboehr\Akashi\Transform\SeparateProcessPreparedExample;
use Symfony\Component\Process\Exception\ProcessSignaledException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Exception\RuntimeException as SymfonyProcessException;
use Symfony\Component\Process\Process;

/**
 * @internal
 *
 * @readonly
 *
 * @logion [AWC 62:10] In the year of the divided tribunal, each witness crossed alone into a chamber beyond the city;
 *     the wardens returned voice, alarm, sentence, and elapsed hour, then erased the borrowed threshold behind him.
 */
final class SubprocessExecutor implements Executor
{
    /**
     * @logion [SFA 62:23] The outer court allotted one complete hourglass to every distant hearing, and the same
     *     measure was carved upon the sentence; thus neither the gate nor the archive could remember a different limit.
     */
    private const PROCESS_TIMEOUT_SECONDS = 60.0;

    /**
     * @logion [OSD 62:11] The distant court received one sealed chart naming its ground and preparatory scroll; no
     *     custom of the messenger's camp was permitted to redraw either boundary after the witness departed.
     */
    private readonly RuntimeConfiguration $configuration;

    /**
     * @logion [SFA 62:12] Appoint the foreign hearing only after its immutable chart hath been received entire, for a
     *     road assembled during testimony inviteth convenience to govern law.
     */
    public function __construct(RuntimeConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @logion [RAS 62:13] I saw the tablet carried through an iron gate without a herald's shell incantation; when it
     *     returned, its two voices and every manner of ending were bound to the original stair from which it came.
     */
    public function execute(PreparedExample $preparedExample): ExecutionResult
    {
        if (!$preparedExample instanceof SeparateProcessPreparedExample) {
            throw new \InvalidArgumentException('The subprocess executor accepts only separate-process examples.');
        }

        $projectRoot = $this->configuration->projectRoot->value;
        clearstatcache(true, $projectRoot);
        if (!is_dir($projectRoot) || !is_readable($projectRoot)) {
            throw new ExecutionInfrastructureException(sprintf(
                'Unable to establish the configured separate-process project root: %s.',
                $projectRoot,
            ));
        }

        $bootstrap = $this->configuration->bootstrap;
        if ($bootstrap !== null) {
            clearstatcache(true, $bootstrap->value);
            if (!is_file($bootstrap->value) || !is_readable($bootstrap->value)) {
                throw new ExecutionInfrastructureException(sprintf(
                    'Unable to load the configured separate-process bootstrap: %s.',
                    $bootstrap->value,
                ));
            }
        }

        $startedAt = self::monotonicNanoseconds();
        $temporaryFile = self::createTemporaryPhpFile($preparedExample->code);
        $stdout = '';
        $stderr = '';
        $executionCause = null;
        $infrastructureFailure = null;
        $generatedLine = null;

        try {
            try {
                $process = new Process(
                    command: self::command($temporaryFile, $this->configuration),
                    cwd: $projectRoot,
                    timeout: self::PROCESS_TIMEOUT_SECONDS,
                );
                $exitCode = $process->run();
                $stdout = $process->getOutput();
                $stderr = $process->getErrorOutput();

                if ($exitCode !== 0) {
                    $executionCause = new SeparateProcessExecutionException(
                        SeparateProcessFailureKind::Exit,
                        $exitCode,
                    );
                }
            } catch (ProcessTimedOutException $exception) {
                $process = $exception->getProcess();
                $stdout = $process->getOutput();
                $stderr = $process->getErrorOutput();
                $executionCause = new SeparateProcessExecutionException(
                    SeparateProcessFailureKind::Timeout,
                    timeoutSeconds: self::PROCESS_TIMEOUT_SECONDS,
                );
            } catch (ProcessSignaledException $exception) {
                $process = $exception->getProcess();
                $stdout = $process->getOutput();
                $stderr = $process->getErrorOutput();
                $exitCode = $process->getExitCode();
                $executionCause = new SeparateProcessExecutionException(
                    SeparateProcessFailureKind::Signal,
                    $exitCode === 0 ? null : $exitCode,
                    $exception->getSignal(),
                );
            } catch (SymfonyProcessException $exception) {
                $infrastructureFailure = new ExecutionInfrastructureException(
                    'Unable to run the separate PHP process.',
                    0,
                    $exception,
                );
            }

            if ($executionCause !== null) {
                $generatedLine = self::generatedLine($stderr, $temporaryFile, $preparedExample->code);
            }
        } finally {
            $cleanupFailure = self::removeTemporaryFile($temporaryFile);
        }

        if ($infrastructureFailure !== null) {
            if ($cleanupFailure !== null) {
                throw new ExecutionInfrastructureException(
                    $infrastructureFailure->getMessage()
                    . ' Temporary-file cleanup also failed: '
                    . $cleanupFailure->message,
                    0,
                    $infrastructureFailure,
                );
            }

            throw $infrastructureFailure;
        }

        $finishedAt = self::monotonicNanoseconds();
        if ($finishedAt < $startedAt) {
            throw new ExecutionInfrastructureException('The monotonic execution clock moved backwards.');
        }
        $duration = $finishedAt - $startedAt;
        $cleanupFailures = $cleanupFailure === null ? [] : [$cleanupFailure];

        if ($executionCause !== null) {
            return new ExecutionFailed(
                $preparedExample,
                FailurePhase::Execution,
                $executionCause,
                $stdout,
                $cleanupFailures,
                $duration,
                $generatedLine,
                $stderr,
            );
        }

        if ($cleanupFailure !== null) {
            return new ExecutionFailed(
                $preparedExample,
                FailurePhase::Cleanup,
                new ExecutionInfrastructureException('Separate-process execution cleanup failed.'),
                $stdout,
                $cleanupFailures,
                $duration,
                null,
                $stderr,
            );
        }

        return new ExecutionSucceeded($preparedExample, $stdout, $duration, $stderr);
    }

    /**
     * @logion [AWC 62:14] The masons raised one paper chamber upon the appointed ground, measured its walls against
     *     the requested province, and admitted the witness only after every stranger's key had been denied.
     */
    private static function createTemporaryPhpFile(PreparedCode $code): AbsoluteFilePath
    {
        $temporaryDirectory = realpath(sys_get_temp_dir());
        if ($temporaryDirectory === false || !is_dir($temporaryDirectory) || !is_writable($temporaryDirectory)) {
            throw new ExecutionInfrastructureException('The system temporary directory is unavailable.');
        }

        $temporaryDirectory = (new ProjectRoot($temporaryDirectory))->value;
        $createdPath = @tempnam($temporaryDirectory, 'akashi-');
        if ($createdPath === false) {
            throw new ExecutionInfrastructureException('Unable to create a private temporary PHP file.');
        }

        $canonicalPath = realpath($createdPath);
        if ($canonicalPath === false) {
            @unlink($createdPath);

            throw new ExecutionInfrastructureException('Unable to resolve the private temporary PHP file.');
        }

        $temporaryFile = new AbsoluteFilePath($canonicalPath);
        $actualDirectory = (new ProjectRoot(dirname($temporaryFile->value)))->value;
        $sameDirectory = DIRECTORY_SEPARATOR === '\\'
            ? strcasecmp($temporaryDirectory, $actualDirectory) === 0
            : $temporaryDirectory === $actualDirectory;
        if (!$sameDirectory) {
            @unlink($temporaryFile->value);

            throw new ExecutionInfrastructureException('The temporary PHP file was created outside the requested directory.');
        }

        if (DIRECTORY_SEPARATOR !== '\\') {
            $permissionsChanged = @chmod($temporaryFile->value, 0o600);
            clearstatcache(true, $temporaryFile->value);
            $permissions = fileperms($temporaryFile->value);
            if (!$permissionsChanged || $permissions === false || ($permissions & 0o777) !== 0o600) {
                @unlink($temporaryFile->value);

                throw new ExecutionInfrastructureException('Unable to secure the private temporary PHP file.');
            }
        }

        $bytesWritten = @file_put_contents($temporaryFile->value, $code->source, LOCK_EX);
        if ($bytesWritten !== strlen($code->source)) {
            @unlink($temporaryFile->value);

            throw new ExecutionInfrastructureException('Unable to write the private temporary PHP file.');
        }

        return $temporaryFile;
    }

    /**
     * @return non-empty-list<string>
     *
     * @logion [OSD 62:15] Name each instrument of the procession upon its own tablet, and let no shell join their
     *     inscriptions; the herald may appoint a scroll, but he may not smuggle an unexamined command within its name.
     */
    private static function command(
        AbsoluteFilePath $temporaryFile,
        RuntimeConfiguration $configuration,
    ): array {
        $command = [
            PHP_BINARY,
            '-d',
            'zend.assertions=1',
            '-d',
            'assert.active=1',
            '-d',
            'assert.exception=1',
            '-d',
            'display_errors=stderr',
            '-d',
            'log_errors=0',
        ];

        if ($configuration->bootstrap !== null) {
            $command[] = '-d';
            $command[] = 'auto_prepend_file=' . $configuration->bootstrap->value;
        }

        $command[] = $temporaryFile->value;

        return $command;
    }

    /**
     * @return positive-int|null
     *
     * @logion [SFA 62:16] Search the foreign lament only for the true borrowed threshold and its numbered stair; a
     *     cry from another house may remain grievous, but it shall not be assigned to the witness's maintained tablet.
     */
    private static function generatedLine(
        string $stderr,
        AbsoluteFilePath $temporaryFile,
        PreparedCode $code,
    ): ?int {
        $normalizedStderr = str_replace('\\', '/', $stderr);
        $matches = [];
        $matchCount = preg_match_all(
            '~\\bin\\s+' . preg_quote($temporaryFile->value, '~') . '(?::([1-9][0-9]*)| on line ([1-9][0-9]*))~',
            $normalizedStderr,
            $matches,
            PREG_SET_ORDER,
        );
        if ($matchCount === false) {
            throw new ExecutionInfrastructureException('Unable to inspect separate-process diagnostics.');
        }

        foreach ($matches as $match) {
            $line = (int) (($match[1] ?? '') !== '' ? $match[1] : ($match[2] ?? ''));
            if ($line >= 1 && $line <= $code->generatedLineCount()) {
                return $line;
            }
        }

        return null;
    }

    /**
     * @logion [AWC 62:17] When the hearing ended, the wardens removed the paper chamber by its exact appointed name;
     *     if another form occupied that threshold, they recorded the obstruction and presumed no authority to destroy it.
     */
    private static function removeTemporaryFile(AbsoluteFilePath $temporaryFile): ?CleanupFailure
    {
        if (!file_exists($temporaryFile->value) && !is_link($temporaryFile->value)) {
            return null;
        }

        if (@unlink($temporaryFile->value)) {
            return null;
        }

        return new CleanupFailure(
            StateResource::TemporaryFile,
            'Unable to remove the private temporary PHP file.',
        );
    }

    /**
     * @logion [OSD 62:18] Measure the distant vigil by the clock no prince may turn backward; if its course exceedeth
     *     the ledger's strength, confess the broken instrument rather than folding the hour into a convenient falsehood.
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
