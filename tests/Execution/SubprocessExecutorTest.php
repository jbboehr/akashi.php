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

namespace jbboehr\Akashi\Tests\Execution;

use jbboehr\Akashi\Document;
use jbboehr\Akashi\Example;
use jbboehr\Akashi\Execution\Exception\ExecutionInfrastructureException;
use jbboehr\Akashi\Execution\Exception\SeparateProcessExecutionException;
use jbboehr\Akashi\Execution\ExecutionFailed;
use jbboehr\Akashi\Execution\ExecutionSucceeded;
use jbboehr\Akashi\Execution\Executor;
use jbboehr\Akashi\Execution\FailurePhase;
use jbboehr\Akashi\Execution\Process\SubprocessExecutor;
use jbboehr\Akashi\Execution\RuntimeConfiguration;
use jbboehr\Akashi\Execution\SeparateProcessFailureKind;
use jbboehr\Akashi\Execution\StateResource;
use jbboehr\Akashi\Model\ExampleCode;
use jbboehr\Akashi\Model\ExampleId;
use jbboehr\Akashi\Model\FenceMetadata;
use jbboehr\Akashi\Model\Language;
use jbboehr\Akashi\Model\SourceLocation;
use jbboehr\Akashi\Model\SourceSpan;
use jbboehr\Akashi\Transform\ExecutionScope;
use jbboehr\Akashi\Transform\InProcessTransformer;
use jbboehr\Akashi\Transform\SeparateProcessPreparedExample;
use jbboehr\Akashi\Transform\SeparateProcessTransformer;
use PHPUnit\Framework\TestCase;

final class SubprocessExecutorTest extends TestCase
{
    private string $workspace;

    protected function setUp(): void
    {
        $workspace = tempnam(sys_get_temp_dir(), 'akashi-subprocess-');
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
                continue;
            }

            self::assertTrue(rmdir($path->getPathname()));
        }

        self::assertTrue(rmdir($this->workspace));
    }

    public function testImplementsTheExecutorContractAndUsesAPrivateTemporaryFile(): void
    {
        $source = <<<'PHP'
$permissions = fileperms(__FILE__);
echo json_encode([
    'file' => __FILE__,
    'cwd' => getcwd(),
    'permissions' => $permissions === false ? null : sprintf('%o', $permissions & 0777),
], JSON_THROW_ON_ERROR);
file_put_contents('php://stderr', 'documented warning');
PHP;
        $executor = $this->executor();
        $prepared = $this->transform($source);
        $startedAt = hrtime(true);
        self::assertIsInt($startedAt);

        $result = $executor->execute($prepared);
        $finishedAt = hrtime(true);
        self::assertIsInt($finishedAt);

        self::assertTrue((new \ReflectionClass($executor))->implementsInterface(Executor::class));
        self::assertInstanceOf(ExecutionSucceeded::class, $result);
        self::assertSame($prepared, $result->preparedExample);
        self::assertSame('documented warning', $result->stderr);
        self::assertGreaterThanOrEqual(0, $result->durationNanoseconds);
        self::assertLessThanOrEqual($finishedAt - $startedAt, $result->durationNanoseconds);

        $details = json_decode($result->stdout, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($details);
        self::assertIsString($details['file']);
        self::assertSame($this->workspace, $details['cwd']);
        self::assertSame(realpath(sys_get_temp_dir()), dirname($details['file']));
        self::assertFileDoesNotExist($details['file']);
        if (DIRECTORY_SEPARATOR !== '\\') {
            self::assertSame('600', $details['permissions']);
        }
    }

    public function testUsesTheConfiguredBootstrapWithoutInjectingItIntoPreparedSource(): void
    {
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/bootstrap with spaces.php',
            "<?php\nconst AKASHI_BOOTSTRAP_VALUE = 'bootstrapped';",
        ));
        $configuration = RuntimeConfiguration::forProject($this->workspace)
            ->withBootstrap('bootstrap with spaces.php');
        $prepared = $this->transform('echo AKASHI_BOOTSTRAP_VALUE;');

        $result = (new SubprocessExecutor($configuration))->execute($prepared);

        self::assertInstanceOf(ExecutionSucceeded::class, $result);
        self::assertSame('bootstrapped', $result->stdout);
        self::assertSame('', $result->stderr);
        self::assertStringNotContainsString('auto_prepend_file', $prepared->code->source);
        self::assertStringNotContainsString('bootstrap with spaces.php', $prepared->code->source);
    }

    public function testTreatsAuthoredExitZeroAsSuccess(): void
    {
        $result = $this->executor()->execute($this->transform("echo 'before'; exit(0); echo 'after';"));

        self::assertInstanceOf(ExecutionSucceeded::class, $result);
        self::assertSame('before', $result->stdout);
        self::assertSame('', $result->stderr);
    }

    public function testReturnsANonzeroExitWithTypedMetadataAndSeparatedOutput(): void
    {
        $source = "echo 'before'; file_put_contents('php://stderr', 'authored warning'); exit(7);";

        $result = $this->executor()->execute($this->transform($source));

        self::assertInstanceOf(ExecutionFailed::class, $result);
        self::assertSame(FailurePhase::Execution, $result->phase);
        self::assertInstanceOf(SeparateProcessExecutionException::class, $result->cause);
        self::assertSame(SeparateProcessFailureKind::Exit, $result->cause->kind);
        self::assertSame(7, $result->cause->exitCode);
        self::assertNull($result->cause->termSignal);
        self::assertSame('before', $result->stdout);
        self::assertSame('authored warning', $result->stderr);
        self::assertSame([], $result->cleanupFailures);
        self::assertNull($result->generatedLine);
    }

    public function testMapsFatalDiagnosticsBackToTheMaintainedSource(): void
    {
        $prepared = $this->transform("echo 'before';\nundefined_akashi_function();");

        $result = $this->executor()->execute($prepared);

        self::assertInstanceOf(ExecutionFailed::class, $result);
        self::assertInstanceOf(SeparateProcessExecutionException::class, $result->cause);
        self::assertSame(SeparateProcessFailureKind::Exit, $result->cause->kind);
        self::assertSame('before', $result->stdout);
        self::assertStringContainsString('undefined_akashi_function', $result->stderr);
        self::assertSame(3, $result->generatedLine);
        self::assertSame(11, $prepared->sourceMap->sourceLineFor($result->generatedLine));
    }

    public function testMapsAFailureOnTheFirstGeneratedLine(): void
    {
        $prepared = $this->transform('<?php undefined_akashi_function();');

        $result = $this->executor()->execute($prepared);

        self::assertInstanceOf(ExecutionFailed::class, $result);
        self::assertSame(1, $result->generatedLine);
        self::assertSame(10, $prepared->sourceMap->sourceLineFor($result->generatedLine));
    }

    public function testRecognizesThePhpOnLineDiagnosticForm(): void
    {
        $source = <<<'PHP'
file_put_contents('php://stderr', 'Fatal error: fixture in ' . __FILE__ . ' on line 2');
exit(9);
PHP;
        $prepared = $this->transform($source);

        $result = $this->executor()->execute($prepared);

        self::assertInstanceOf(ExecutionFailed::class, $result);
        self::assertSame(2, $result->generatedLine);
        self::assertSame(10, $prepared->sourceMap->sourceLineFor($result->generatedLine));
    }

    public function testRejectsADiagnosticLineOutsideThePreparedSource(): void
    {
        $source = <<<'PHP'
file_put_contents('php://stderr', 'Fatal error: fixture in ' . __FILE__ . ':999');
exit(9);
PHP;

        $result = $this->executor()->execute($this->transform($source));

        self::assertInstanceOf(ExecutionFailed::class, $result);
        self::assertNull($result->generatedLine);
    }

    public function testEnablesNativeAssertionsInTheChildProcess(): void
    {
        $prepared = $this->transform("echo 'before';\nassert(false, 'documented failure');");

        $result = $this->executor()->execute($prepared);

        self::assertInstanceOf(ExecutionFailed::class, $result);
        self::assertInstanceOf(SeparateProcessExecutionException::class, $result->cause);
        self::assertSame(SeparateProcessFailureKind::Exit, $result->cause->kind);
        self::assertSame('before', $result->stdout);
        self::assertStringContainsString('AssertionError', $result->stderr);
        self::assertStringContainsString('documented failure', $result->stderr);
        self::assertSame(3, $result->generatedLine);
        self::assertSame(11, $prepared->sourceMap->sourceLineFor($result->generatedLine));
    }

    public function testReportsTerminationBySignalWhenThePlatformSupportsIt(): void
    {
        if (!function_exists('posix_kill') || !defined('SIGTERM')) {
            self::markTestSkipped('The POSIX process extension is unavailable.');
        }
        $signal = constant('SIGTERM');

        $result = $this->executor()->execute(
            $this->transform("echo 'before'; posix_kill(getmypid(), SIGTERM);"),
        );

        self::assertInstanceOf(ExecutionFailed::class, $result);
        self::assertInstanceOf(SeparateProcessExecutionException::class, $result->cause);
        self::assertSame(SeparateProcessFailureKind::Signal, $result->cause->kind);
        self::assertSame($signal, $result->cause->termSignal);
        self::assertNotSame(0, $result->cause->exitCode);
        self::assertSame('before', $result->stdout);
    }

    public function testReportsCleanupFailureWithoutRecursivelyDeletingAReplacementDirectory(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            self::markTestSkipped('Windows does not permit a running PHP file to replace itself portably.');
        }

        $source = '$path = __FILE__; echo $path; unlink($path); mkdir($path);';
        $result = $this->executor()->execute($this->transform($source));

        self::assertInstanceOf(ExecutionFailed::class, $result);
        $temporaryPath = $result->stdout;

        try {
            self::assertSame(FailurePhase::Cleanup, $result->phase);
            self::assertInstanceOf(ExecutionInfrastructureException::class, $result->cause);
            self::assertSame('', $result->stderr);
            self::assertCount(1, $result->cleanupFailures);
            self::assertSame(StateResource::TemporaryFile, $result->cleanupFailures[0]->resource);
            self::assertDirectoryExists($temporaryPath);
        } finally {
            if (is_dir($temporaryPath)) {
                self::assertTrue(rmdir($temporaryPath));
            }
        }
    }

    public function testRemovesABrokenSymlinkThatReplacedTheTemporaryFile(): void
    {
        if (DIRECTORY_SEPARATOR === '\\' || !function_exists('symlink')) {
            self::markTestSkipped('Replacing a running file with a symlink is unavailable.');
        }

        $source = '$path = __FILE__; echo $path; unlink($path); symlink($path . ".missing", $path);';
        $result = $this->executor()->execute($this->transform($source));

        self::assertInstanceOf(ExecutionSucceeded::class, $result);
        $temporaryPath = $result->stdout;

        try {
            self::assertFalse(is_link($temporaryPath));
            self::assertFileDoesNotExist($temporaryPath);
        } finally {
            if (is_link($temporaryPath)) {
                self::assertTrue(unlink($temporaryPath));
            }
        }
    }

    public function testReturnsAnInfrastructureFailureWhenTheConfiguredProjectDisappears(): void
    {
        $projectRoot = $this->workspace . '/vanished-project';
        self::assertTrue(mkdir($projectRoot));
        $configuration = RuntimeConfiguration::forProject($projectRoot);
        self::assertTrue(rmdir($projectRoot));

        $result = (new SubprocessExecutor($configuration))->execute($this->transform("echo 'not executed';"));

        self::assertInstanceOf(ExecutionFailed::class, $result);
        self::assertSame(FailurePhase::Execution, $result->phase);
        self::assertInstanceOf(ExecutionInfrastructureException::class, $result->cause);
        self::assertSame('Unable to run the separate PHP process.', $result->cause->getMessage());
        self::assertSame(0, $result->cause->getCode());
        self::assertSame('', $result->stdout);
        self::assertSame('', $result->stderr);
        self::assertSame([], $result->cleanupFailures);
        self::assertNull($result->generatedLine);
    }

    public function testRejectsAnInProcessPreparedExample(): void
    {
        $prepared = (new InProcessTransformer())->transform(
            $this->example("echo 'not executed';"),
            new ExecutionScope('Akashi\\Generated\\SubprocessMismatch'),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('accepts only separate-process examples');

        $this->executor()->execute($prepared);
    }

    private function executor(): SubprocessExecutor
    {
        return new SubprocessExecutor(RuntimeConfiguration::forProject($this->workspace));
    }

    private function transform(string $source): SeparateProcessPreparedExample
    {
        return (new SeparateProcessTransformer())->transform($this->example($source));
    }

    private function example(string $source): Example
    {
        $sourceLength = strlen($source);
        $lineBreaks = preg_match_all('/\r\n|\r|\n/', $source);
        self::assertNotFalse($lineBreaks);
        $lineCount = $lineBreaks + 1;
        if ($sourceLength > 0 && preg_match('/(?:\r\n|\r|\n)\z/', $source) === 1) {
            --$lineCount;
        }

        $firstCodeLine = 10;
        $lastCodeLine = $sourceLength === 0 ? null : $firstCodeLine + $lineCount - 1;
        $closingFenceLine = $lastCodeLine === null ? $firstCodeLine : $lastCodeLine + 1;

        return new Example(
            id: new ExampleId('example-subprocess-executor-01'),
            label: 'Subprocess executor fixture',
            document: new Document('docs/subprocess.md', $source),
            location: new SourceLocation(
                $firstCodeLine - 1,
                $firstCodeLine,
                $lastCodeLine,
                $closingFenceLine,
                new SourceSpan(0, max(1, $sourceLength)),
                new SourceSpan(0, $sourceLength),
            ),
            language: new Language('php'),
            code: new ExampleCode($source),
            fence: new FenceMetadata('php', '`', 3, 0),
            ordinal: 1,
        );
    }
}
