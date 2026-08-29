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

namespace jbboehr\Akashi\Tests\Integration\PHPStan;

use jbboehr\Akashi\Integration\PHPStan\PhpStanCommandResult;
use jbboehr\Akashi\Integration\PHPStan\PhpStanCommandRunner;
use jbboehr\Akashi\Integration\PHPStan\PhpStanCommandTermination;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class PhpStanCommandRunnerTest extends TestCase
{
    private string $workspace;

    protected function setUp(): void
    {
        $workspace = tempnam(sys_get_temp_dir(), 'akashi-phpstan-command-');
        self::assertNotFalse($workspace);
        self::assertTrue(unlink($workspace));
        self::assertTrue(mkdir($workspace, 0o700));

        $this->workspace = $workspace;
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->workspace)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->workspace, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $path) {
            if (!$path instanceof \SplFileInfo) {
                continue;
            }
            if ($path->isLink() || $path->isFile()) {
                self::assertTrue(unlink($path->getPathname()));
            } else {
                self::assertTrue(rmdir($path->getPathname()));
            }
        }

        self::assertTrue(rmdir($this->workspace));
    }

    public function testPreservesArgumentBoundariesWithoutShellInterpretation(): void
    {
        $injectionTarget = $this->workspace . '/must-not-exist';
        $authoredArguments = ['argument with spaces', '', '; touch ' . $injectionTarget];
        $program = <<<'PHP'
fwrite(STDOUT, json_encode(array_slice($argv, 1), JSON_THROW_ON_ERROR));
fwrite(STDERR, 'documented warning');
PHP;
        $startedAt = hrtime(true);
        self::assertIsInt($startedAt);

        $result = (new PhpStanCommandRunner())->run(
            $this->workspace,
            PHP_BINARY,
            ['-r', $program, '--', ...$authoredArguments],
        );
        $finishedAt = hrtime(true);
        self::assertIsInt($finishedAt);

        self::assertSame(PhpStanCommandTermination::Completed, $result->termination);
        self::assertSame(0, $result->exitCode);
        self::assertSame($authoredArguments, json_decode($result->stdout, true, flags: JSON_THROW_ON_ERROR));
        self::assertSame('documented warning', $result->stderr);
        self::assertGreaterThanOrEqual(0, $result->durationNanoseconds);
        self::assertLessThanOrEqual($finishedAt - $startedAt, $result->durationNanoseconds);
        self::assertNull($result->termSignal);
        self::assertNull($result->timeoutSeconds);
        self::assertNull($result->failureMessage);
        self::assertFileDoesNotExist($injectionTarget);
    }

    public function testPreservesANonzeroExitAsACompletedInvocation(): void
    {
        $result = (new PhpStanCommandRunner())->run(
            $this->workspace,
            PHP_BINARY,
            ['-r', "fwrite(STDOUT, 'analysis json'); fwrite(STDERR, 'analysis warning'); exit(7);"],
        );

        self::assertSame(PhpStanCommandTermination::Completed, $result->termination);
        self::assertSame(7, $result->exitCode);
        self::assertSame('analysis json', $result->stdout);
        self::assertSame('analysis warning', $result->stderr);
        self::assertNull($result->termSignal);
        self::assertNull($result->timeoutSeconds);
        self::assertNull($result->failureMessage);
    }

    public function testUsesTheExplicitWorkingDirectoryAndInheritsTheEnvironment(): void
    {
        $variable = 'AKASHI_PHPSTAN_COMMAND_TEST_VALUE';
        $previous = getenv($variable);
        $serverHadVariable = array_key_exists($variable, $_SERVER);
        $previousServerValue = $_SERVER[$variable] ?? null;
        self::assertTrue(putenv($variable . '=inherited-value'));
        $_SERVER[$variable] = 'inherited-value';

        try {
            $result = (new PhpStanCommandRunner())->run(
                $this->workspace,
                PHP_BINARY,
                ['-r', sprintf(
                    'echo json_encode([getcwd(), getenv(%s)], JSON_THROW_ON_ERROR);',
                    var_export($variable, true),
                )],
            );
        } finally {
            self::assertTrue(putenv($previous === false ? $variable : $variable . '=' . $previous));
            if ($serverHadVariable) {
                $_SERVER[$variable] = $previousServerValue;
            } else {
                unset($_SERVER[$variable]);
            }
        }

        self::assertSame(PhpStanCommandTermination::Completed, $result->termination);
        self::assertSame(
            [realpath($this->workspace), 'inherited-value'],
            json_decode($result->stdout, true, flags: JSON_THROW_ON_ERROR),
        );
    }

    public function testReturnsTypedInfrastructureEvidenceWhenTheMonotonicClockIsUnavailable(): void
    {
        $result = $this->runIsolatedWithDisabledFunction('hrtime');

        self::assertSame('infrastructure-failed', $result['termination']);
        self::assertSame(0, $result['durationNanoseconds']);
        self::assertSame('The monotonic PHPStan command clock is unavailable.', $result['failureMessage']);
    }

    public function testReturnsTypedInfrastructureEvidenceWhenProcessConstructionFails(): void
    {
        $result = $this->runIsolatedWithDisabledFunction('proc_open');

        self::assertSame('infrastructure-failed', $result['termination']);
        self::assertStringContainsString('proc_open', $result['failureMessage']);
    }

    public function testPreservesPartialStreamsAndTheEnforcedTimeout(): void
    {
        $result = (new PhpStanCommandRunner())->run(
            $this->workspace,
            PHP_BINARY,
            ['-r', "fwrite(STDOUT, 'before'); fwrite(STDERR, 'warning'); usleep(500000);"],
            0.05,
        );

        self::assertSame(PhpStanCommandTermination::TimedOut, $result->termination);
        self::assertNull($result->exitCode);
        self::assertSame('before', $result->stdout);
        self::assertSame('warning', $result->stderr);
        self::assertNull($result->termSignal);
        self::assertSame(0.05, $result->timeoutSeconds);
        self::assertNull($result->failureMessage);
    }

    public function testPreservesSignalEvidenceWhereThePlatformSupportsIt(): void
    {
        if (DIRECTORY_SEPARATOR === '\\' || !function_exists('posix_kill')) {
            self::markTestSkipped('Process-signal evidence requires a Unix-like platform with ext-posix.');
        }

        $result = (new PhpStanCommandRunner())->run(
            $this->workspace,
            PHP_BINARY,
            ['-r', "fwrite(STDOUT, 'before'); fwrite(STDERR, 'warning'); posix_kill(getmypid(), 15); usleep(500000);"],
        );

        self::assertSame(PhpStanCommandTermination::Signaled, $result->termination);
        self::assertSame('before', $result->stdout);
        self::assertSame('warning', $result->stderr);
        self::assertSame(15, $result->termSignal);
        self::assertNull($result->timeoutSeconds);
        self::assertNull($result->failureMessage);
    }

    public function testReturnsTypedInfrastructureEvidenceForAMissingProjectRoot(): void
    {
        $missingRoot = $this->workspace . '/missing';

        $result = (new PhpStanCommandRunner())->run($missingRoot, PHP_BINARY);

        self::assertSame(PhpStanCommandTermination::InfrastructureFailed, $result->termination);
        self::assertNull($result->exitCode);
        self::assertSame('', $result->stdout);
        self::assertSame('', $result->stderr);
        self::assertNull($result->termSignal);
        self::assertNull($result->timeoutSeconds);
        self::assertSame(
            'PHPStan command project root does not exist or is not a readable directory: '
                . str_replace('\\', '/', $missingRoot)
                . '.',
            $result->failureMessage,
        );
    }

    public function testReturnsTypedInfrastructureEvidenceWhenTheProjectRootIsAFile(): void
    {
        $file = $this->workspace . '/not-a-directory';
        self::assertNotFalse(file_put_contents($file, 'not a project root'));

        $result = (new PhpStanCommandRunner())->run($file, PHP_BINARY);

        self::assertSame(PhpStanCommandTermination::InfrastructureFailed, $result->termination);
        self::assertSame(
            'PHPStan command project root does not exist or is not a readable directory: '
                . str_replace('\\', '/', $file)
                . '.',
            $result->failureMessage,
        );
    }

    public function testReturnsTypedInfrastructureEvidenceForAMissingExecutable(): void
    {
        $missingExecutable = $this->workspace . '/missing-php';

        $result = (new PhpStanCommandRunner())->run($this->workspace, $missingExecutable);

        self::assertSame(PhpStanCommandTermination::InfrastructureFailed, $result->termination);
        self::assertNull($result->exitCode);
        self::assertNull($result->termSignal);
        self::assertNull($result->timeoutSeconds);
        self::assertSame(
            'PHPStan command executable does not exist or is not a file: '
                . str_replace('\\', '/', $missingExecutable)
                . '.',
            $result->failureMessage,
        );
    }

    public function testPreservesAProcessComponentFallbackAsRawExitEvidence(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            self::markTestSkipped('The POSIX missing-interpreter fallback does not apply on Windows.');
        }

        $executable = $this->workspace . '/missing-interpreter';
        $injectionTarget = $this->workspace . '/must-not-exist-from-fallback';
        self::assertNotFalse(file_put_contents($executable, "#!/akashi/missing/interpreter\n"));
        self::assertTrue(chmod($executable, 0o700));

        $result = (new PhpStanCommandRunner())->run(
            $this->workspace,
            $executable,
            ['; touch ' . $injectionTarget],
        );

        self::assertSame(PhpStanCommandTermination::Completed, $result->termination);
        self::assertSame(127, $result->exitCode);
        self::assertSame('', $result->stdout);
        self::assertNull($result->failureMessage);
        self::assertFileDoesNotExist($injectionTarget);
    }

    public function testRunsAnExecuteOnlyBinaryOnLinux(): void
    {
        if (PHP_OS_FAMILY !== 'Linux' || (function_exists('posix_geteuid') && posix_geteuid() === 0)) {
            self::markTestSkipped('Execute-only PHP binary behavior requires a non-root Linux user.');
        }

        $executable = $this->workspace . '/execute-only-php';
        self::assertTrue(copy(PHP_BINARY, $executable));
        self::assertTrue(chmod($executable, 0o111));
        clearstatcache(true, $executable);
        self::assertFalse(is_readable($executable));
        self::assertTrue(is_executable($executable));

        $result = (new PhpStanCommandRunner())->run(
            $this->workspace,
            $executable,
            ['-r', "echo 'executed';"],
        );

        self::assertSame(PhpStanCommandTermination::Completed, $result->termination);
        self::assertSame(0, $result->exitCode);
        self::assertSame('executed', $result->stdout);
    }

    public function testReturnsTypedInfrastructureEvidenceForANonExecutableFileOnUnix(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            self::markTestSkipped('Unix executable permission checks do not apply on Windows.');
        }

        $executable = $this->workspace . '/not-executable';
        self::assertNotFalse(file_put_contents($executable, "#!/bin/sh\nexit 0\n"));
        self::assertTrue(chmod($executable, 0o600));

        $result = (new PhpStanCommandRunner())->run($this->workspace, $executable);

        self::assertSame(PhpStanCommandTermination::InfrastructureFailed, $result->termination);
        self::assertSame('PHPStan command file is not executable: ' . $executable . '.', $result->failureMessage);
    }

    public function testReturnsTypedInfrastructureEvidenceWhenTheExecutableIsADirectory(): void
    {
        $directory = $this->workspace . '/not-a-file';
        self::assertTrue(mkdir($directory));

        $result = (new PhpStanCommandRunner())->run($this->workspace, $directory);

        self::assertSame(PhpStanCommandTermination::InfrastructureFailed, $result->termination);
        self::assertSame(
            'PHPStan command executable does not exist or is not a file: '
                . str_replace('\\', '/', $directory)
                . '.',
            $result->failureMessage,
        );
    }

    public function testAcceptsTheSmallestPositiveSignalAsResultEvidence(): void
    {
        $result = new PhpStanCommandResult(
            PhpStanCommandTermination::Signaled,
            'partial output',
            'partial error',
            0,
            termSignal: 1,
        );

        self::assertSame(1, $result->termSignal);
        self::assertNull($result->exitCode);
        self::assertNull($result->timeoutSeconds);
        self::assertNull($result->failureMessage);
    }

    /** @param array<array-key, mixed> $arguments */
    #[DataProvider('invalidInvocationProvider')]
    public function testRejectsMalformedInvocationInput(
        string $projectRoot,
        string $executable,
        array $arguments,
        float $timeoutSeconds,
        string $message,
    ): void {
        try {
            (new \ReflectionMethod(PhpStanCommandRunner::class, 'run'))->invoke(
                new PhpStanCommandRunner(),
                $projectRoot,
                $executable,
                $arguments,
                $timeoutSeconds,
            );
        } catch (\InvalidArgumentException $exception) {
            self::assertSame($message, $exception->getMessage());

            return;
        }

        self::fail('Malformed PHPStan command input must be rejected.');
    }

    /** @return iterable<string, array{string, string, array<array-key, mixed>, float, string}> */
    public static function invalidInvocationProvider(): iterable
    {
        yield 'relative project root' => [
            'relative',
            PHP_BINARY,
            [],
            1.0,
            'Project root must be an absolute path.',
        ];
        yield 'relative executable' => [
            sys_get_temp_dir(),
            'relative',
            [],
            1.0,
            'Absolute file path must be absolute.',
        ];
        yield 'non-list arguments' => [
            sys_get_temp_dir(),
            PHP_BINARY,
            ['argument' => 'value'],
            1.0,
            'PHPStan command arguments must form a list.',
        ];
        yield 'non-string argument' => [
            sys_get_temp_dir(),
            PHP_BINARY,
            [1],
            1.0,
            'Every PHPStan command argument must be a string.',
        ];
        yield 'NUL argument' => [
            sys_get_temp_dir(),
            PHP_BINARY,
            ["bad\0argument"],
            1.0,
            'PHPStan command arguments must not contain NUL bytes.',
        ];
        yield 'zero timeout' => [
            sys_get_temp_dir(),
            PHP_BINARY,
            [],
            0.0,
            'PHPStan command timeout must be finite and positive.',
        ];
        yield 'infinite timeout' => [
            sys_get_temp_dir(),
            PHP_BINARY,
            [],
            INF,
            'PHPStan command timeout must be finite and positive.',
        ];
        yield 'not-a-number timeout' => [
            sys_get_temp_dir(),
            PHP_BINARY,
            [],
            NAN,
            'PHPStan command timeout must be finite and positive.',
        ];
    }

    /** @param array{PhpStanCommandTermination, string, string, int, int|null, int|null, float|null, string|null} $arguments */
    #[DataProvider('invalidResultProvider')]
    public function testRejectsContradictoryResultEvidence(array $arguments, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new PhpStanCommandResult(...$arguments);
    }

    /**
     * @return iterable<string, array{
     *     array{PhpStanCommandTermination, string, string, int, int|null, int|null, float|null, string|null},
     *     string
     * }>
     */
    public static function invalidResultProvider(): iterable
    {
        yield 'negative duration' => [
            [PhpStanCommandTermination::Completed, '', '', -1, 0, null, null, null],
            'duration must not be negative',
        ];
        yield 'completed without exit' => [
            [PhpStanCommandTermination::Completed, '', '', 0, null, null, null, null],
            'requires an exit code',
        ];
        yield 'completed with signal' => [
            [PhpStanCommandTermination::Completed, '', '', 0, 0, 15, null, null],
            'requires an exit code and no signal',
        ];
        yield 'completed with timeout' => [
            [PhpStanCommandTermination::Completed, '', '', 0, 0, null, 1.0, null],
            'requires an exit code and no signal',
        ];
        yield 'completed with infrastructure failure' => [
            [PhpStanCommandTermination::Completed, '', '', 0, 0, null, null, 'failure'],
            'requires an exit code and no signal',
        ];
        yield 'timeout without limit' => [
            [PhpStanCommandTermination::TimedOut, '', '', 0, null, null, null, null],
            'requires a finite positive timeout',
        ];
        yield 'timeout with infinite limit' => [
            [PhpStanCommandTermination::TimedOut, '', '', 0, null, null, INF, null],
            'requires a finite positive timeout',
        ];
        yield 'timeout with zero limit' => [
            [PhpStanCommandTermination::TimedOut, '', '', 0, null, null, 0.0, null],
            'requires a finite positive timeout',
        ];
        yield 'timeout with exit' => [
            [PhpStanCommandTermination::TimedOut, '', '', 0, 1, null, 1.0, null],
            'requires a finite positive timeout',
        ];
        yield 'timeout with signal' => [
            [PhpStanCommandTermination::TimedOut, '', '', 0, null, 15, 1.0, null],
            'requires a finite positive timeout',
        ];
        yield 'signal without number' => [
            [PhpStanCommandTermination::Signaled, '', '', 0, null, null, null, null],
            'requires a positive signal',
        ];
        yield 'signal with zero number' => [
            [PhpStanCommandTermination::Signaled, '', '', 0, null, 0, null, null],
            'requires a positive signal',
        ];
        yield 'signal with timeout' => [
            [PhpStanCommandTermination::Signaled, '', '', 0, null, 15, 1.0, null],
            'requires a positive signal',
        ];
        yield 'signal with successful exit' => [
            [PhpStanCommandTermination::Signaled, '', '', 0, 0, 15, null, null],
            'requires a positive signal, no successful exit code',
        ];
        yield 'infrastructure failure without message' => [
            [PhpStanCommandTermination::InfrastructureFailed, '', '', 0, null, null, null, null],
            'requires a nonempty message',
        ];
        yield 'infrastructure failure with blank message' => [
            [PhpStanCommandTermination::InfrastructureFailed, '', '', 0, null, null, null, ' '],
            'requires a nonempty message',
        ];
        yield 'infrastructure failure with exit' => [
            [PhpStanCommandTermination::InfrastructureFailed, '', '', 0, 1, null, null, 'failure'],
            'requires a nonempty message',
        ];
        yield 'infrastructure failure with signal' => [
            [PhpStanCommandTermination::InfrastructureFailed, '', '', 0, null, 15, null, 'failure'],
            'requires a nonempty message',
        ];
        yield 'infrastructure failure with timeout' => [
            [PhpStanCommandTermination::InfrastructureFailed, '', '', 0, null, null, 1.0, 'failure'],
            'requires a nonempty message',
        ];
    }

    /**
     * @return array{
     *     termination: string,
     *     durationNanoseconds: int,
     *     failureMessage: string,
     * }
     */
    private function runIsolatedWithDisabledFunction(string $disabledFunction): array
    {
        $autoload = dirname(__DIR__, 3) . '/vendor/autoload.php';
        $script = sprintf(
            <<<'PHP'
require %s;
$result = (new \jbboehr\Akashi\Integration\PHPStan\PhpStanCommandRunner())->run(%s, %s);
echo json_encode([
    'termination' => $result->termination->value,
    'durationNanoseconds' => $result->durationNanoseconds,
    'failureMessage' => $result->failureMessage,
], JSON_THROW_ON_ERROR);
PHP,
            var_export($autoload, true),
            var_export($this->workspace, true),
            var_export(PHP_BINARY, true),
        );
        $process = new Process([
            PHP_BINARY,
            '-d',
            'disable_functions=' . $disabledFunction,
            '-r',
            $script,
        ]);
        $exitCode = $process->run();
        self::assertSame(0, $exitCode, $process->getErrorOutput());

        $decoded = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            self::fail('The isolated command result must decode to an array.');
        }
        self::assertSame(['termination', 'durationNanoseconds', 'failureMessage'], array_keys($decoded));
        $termination = $decoded['termination'] ?? null;
        $durationNanoseconds = $decoded['durationNanoseconds'] ?? null;
        $failureMessage = $decoded['failureMessage'] ?? null;
        if (!is_string($termination) || !is_int($durationNanoseconds) || !is_string($failureMessage)) {
            self::fail('The isolated command result has malformed evidence.');
        }

        return [
            'termination' => $termination,
            'durationNanoseconds' => $durationNanoseconds,
            'failureMessage' => $failureMessage,
        ];
    }
}
