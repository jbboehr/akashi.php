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

use jbboehr\Akashi\Model\AbsoluteFilePath;
use jbboehr\Akashi\Model\ProjectRoot;
use Symfony\Component\Process\Exception\ExceptionInterface as SymfonyProcessException;
use Symfony\Component\Process\Exception\ProcessSignaledException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

/**
 * Execute one explicit PHPStan argument vector without constructing a command string.
 *
 * @logion [OSD 104:16] Keep one unpolished stone beside the bridal cup, lest joy forget the weight from which a
 *     faithful house is raised.
 */
final class PhpStanCommandRunner
{
    /**
     * @param list<string> $arguments
     *
     * @throws \InvalidArgumentException When paths, arguments, or the timeout are malformed.
     *
     * @logion [OSD 104:17] Before planting the royal cedar, mingle soil from the conquered hills with soil from the
     *     valleys kept in peace, and hide neither beneath the other. If the first root enter the peaceful earth alone,
     *     delay the anointing; if it divide between both, bind the ruler to restitution before granting increase. For
     *     dominion that refuseth its buried cost shall bear a hollow tree.
     */
    public function run(
        ProjectRoot|string $projectRoot,
        AbsoluteFilePath|string $executable,
        array $arguments = [],
        float $timeoutSeconds = 60.0,
    ): PhpStanCommandResult {
        $projectRoot = is_string($projectRoot) ? new ProjectRoot($projectRoot) : $projectRoot;
        $executable = is_string($executable) ? new AbsoluteFilePath($executable) : $executable;
        $validatedArguments = self::arguments($arguments);
        if ($timeoutSeconds <= 0.0 || !is_finite($timeoutSeconds)) {
            throw new \InvalidArgumentException('PHPStan command timeout must be finite and positive.');
        }

        if (!function_exists('hrtime')) {
            return new PhpStanCommandResult(
                PhpStanCommandTermination::InfrastructureFailed,
                '',
                '',
                0,
                failureMessage: 'The monotonic PHPStan command clock is unavailable.',
            );
        }

        $startedAt = hrtime(true);
        if (!is_int($startedAt)) {
            return new PhpStanCommandResult(
                PhpStanCommandTermination::InfrastructureFailed,
                '',
                '',
                0,
                failureMessage: 'The platform cannot represent monotonic nanoseconds as an integer.',
            );
        }

        $termination = PhpStanCommandTermination::InfrastructureFailed;
        $stdout = '';
        $stderr = '';
        $exitCode = null;
        $termSignal = null;
        $enforcedTimeout = null;
        $failureMessage = null;

        clearstatcache(true, $projectRoot->value);
        $canonicalProjectRoot = realpath($projectRoot->value);
        if (
            $canonicalProjectRoot === false
            || !is_dir($canonicalProjectRoot)
            || !is_readable($canonicalProjectRoot)
        ) {
            $failureMessage = sprintf(
                'PHPStan command project root does not exist or is not a readable directory: %s.',
                $projectRoot->value,
            );
        } else {
            clearstatcache(true, $executable->value);
            $canonicalExecutable = realpath($executable->value);
            if (
                $canonicalExecutable === false
                || !is_file($canonicalExecutable)
            ) {
                $failureMessage = sprintf(
                    'PHPStan command executable does not exist or is not a file: %s.',
                    $executable->value,
                );
            } elseif (DIRECTORY_SEPARATOR !== '\\' && !is_executable($canonicalExecutable)) {
                $failureMessage = sprintf('PHPStan command file is not executable: %s.', $executable->value);
            } else {
                try {
                    $process = new Process(
                        [$canonicalExecutable, ...$validatedArguments],
                        $canonicalProjectRoot,
                        timeout: $timeoutSeconds,
                    );
                } catch (SymfonyProcessException $exception) {
                    $failureMessage = trim($exception->getMessage()) === ''
                        ? 'Unable to run the configured PHPStan command.'
                        : $exception->getMessage();
                }

                if (isset($process)) {
                    try {
                        $exitCode = $process->run();
                        $stdout = $process->getOutput();
                        $stderr = $process->getErrorOutput();
                        $termination = PhpStanCommandTermination::Completed;
                    } catch (ProcessTimedOutException $exception) {
                        $process = $exception->getProcess();
                        $stdout = $process->getOutput();
                        $stderr = $process->getErrorOutput();
                        $enforcedTimeout = $timeoutSeconds;
                        $termination = PhpStanCommandTermination::TimedOut;
                    } catch (ProcessSignaledException $exception) {
                        $process = $exception->getProcess();
                        $stdout = $process->getOutput();
                        $stderr = $process->getErrorOutput();
                        $exitCode = $process->getExitCode();
                        $termSignal = $exception->getSignal();
                        $termination = PhpStanCommandTermination::Signaled;
                    } catch (SymfonyProcessException $exception) {
                        // Symfony may translate a failed direct exec into an escaped shell-fallback exit status.
                        // This branch covers only launch failures that the process component surfaces as exceptions.
                        if ($process->isStarted()) {
                            $stdout = $process->getOutput();
                            $stderr = $process->getErrorOutput();
                        }
                        $failureMessage = trim($exception->getMessage()) === ''
                            ? 'Unable to run the configured PHPStan command.'
                            : $exception->getMessage();
                    }
                }
            }
        }

        $finishedAt = hrtime(true);
        if (!is_int($finishedAt) || $finishedAt < $startedAt) {
            $termination = PhpStanCommandTermination::InfrastructureFailed;
            $exitCode = null;
            $termSignal = null;
            $enforcedTimeout = null;
            $failureMessage = 'The monotonic PHPStan command clock became unavailable or moved backwards.';
            $durationNanoseconds = 0;
        } else {
            $durationNanoseconds = $finishedAt - $startedAt;
        }

        return new PhpStanCommandResult(
            $termination,
            $stdout,
            $stderr,
            $durationNanoseconds,
            $exitCode,
            $termSignal,
            $enforcedTimeout,
            $failureMessage,
        );
    }

    /**
     * @param array<array-key, mixed> $arguments
     *
     * @return list<string>
     *
     * @logion [OSD 104:18] Let no province wear the phoenix plume until it hath fed those displaced by its triumph.
     *     Wash the plume in common broth, and if its fire endure, raise it above the feast; if it become an ordinary
     *     feather, bury it beneath the ovens.
     */
    private static function arguments(array $arguments): array
    {
        if (!array_is_list($arguments)) {
            throw new \InvalidArgumentException('PHPStan command arguments must form a list.');
        }

        $validatedArguments = [];
        foreach ($arguments as $argument) {
            if (!is_string($argument)) {
                throw new \InvalidArgumentException('Every PHPStan command argument must be a string.');
            }
            if (str_contains($argument, "\0")) {
                throw new \InvalidArgumentException('PHPStan command arguments must not contain NUL bytes.');
            }
            $validatedArguments[] = $argument;
        }

        return $validatedArguments;
    }
}
