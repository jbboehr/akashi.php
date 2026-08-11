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
use jbboehr\Akashi\Execution\ExecutionFailed;
use jbboehr\Akashi\Execution\ExecutionMode;
use jbboehr\Akashi\Execution\ExecutionSucceeded;
use jbboehr\Akashi\Execution\Executor;
use jbboehr\Akashi\Execution\FailurePhase;
use jbboehr\Akashi\Execution\InProcess\InProcessExecutor;
use jbboehr\Akashi\Execution\RuntimeConfiguration;
use jbboehr\Akashi\Execution\StateResource;
use jbboehr\Akashi\Model\DocumentPath;
use jbboehr\Akashi\Model\ExampleCode;
use jbboehr\Akashi\Model\ExampleId;
use jbboehr\Akashi\Model\FenceMetadata;
use jbboehr\Akashi\Model\Language;
use jbboehr\Akashi\Model\SourceLocation;
use jbboehr\Akashi\Model\SourceSpan;
use jbboehr\Akashi\Transform\ExecutionScope;
use jbboehr\Akashi\Transform\InProcessPreparedExample;
use jbboehr\Akashi\Transform\InProcessTransformer;
use jbboehr\Akashi\Transform\PreparedCode;
use jbboehr\Akashi\Transform\PreparedExample;
use jbboehr\Akashi\Transform\SeparateProcessPreparedExample;
use jbboehr\Akashi\Transform\SourceMap;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

final class InProcessExecutorTest extends TestCase
{
    private int $scopeSequence = 0;

    public function testImplementsTheReusableExecutorContractAndCapturesOutput(): void
    {
        $executor = new InProcessExecutor();
        $prepared = $this->transform("echo 'documented output';");
        $startedAt = hrtime(true);
        self::assertIsInt($startedAt);

        $result = $executor->execute($prepared);
        $finishedAt = hrtime(true);
        self::assertIsInt($finishedAt);

        self::assertTrue((new \ReflectionClass($executor))->implementsInterface(Executor::class));
        self::assertInstanceOf(ExecutionSucceeded::class, $result);
        self::assertSame($prepared, $result->preparedExample);
        self::assertSame('documented output', $result->stdout);
        self::assertSame('', $result->stderr);
        self::assertGreaterThanOrEqual(0, $result->durationNanoseconds);
        self::assertLessThanOrEqual($finishedAt - $startedAt, $result->durationNanoseconds);
    }

    public function testExecutesWithAnEmptyLocalVariableScope(): void
    {
        $result = (new InProcessExecutor())->execute(
            $this->transform('echo json_encode(array_keys(get_defined_vars()), JSON_THROW_ON_ERROR);'),
        );

        self::assertInstanceOf(ExecutionSucceeded::class, $result);
        self::assertSame('[]', $result->stdout);
    }

    public function testSupportsCaseInsensitiveOpeningTags(): void
    {
        $result = (new InProcessExecutor())->execute($this->transform("<?PHP echo 'accepted';"));

        self::assertInstanceOf(ExecutionSucceeded::class, $result);
        self::assertSame('accepted', $result->stdout);
    }

    public function testPreservesStrictAndWeakTypingAcrossTheEvaluationBoundary(): void
    {
        $executor = new InProcessExecutor();
        $weak = $executor->execute($this->transform('echo strlen(123);'));
        $strict = $executor->execute($this->transform("declare(strict_types=1);\necho strlen(123);"));

        self::assertInstanceOf(ExecutionSucceeded::class, $weak);
        self::assertSame('3', $weak->stdout);
        self::assertInstanceOf(ExecutionFailed::class, $strict);
        self::assertSame(FailurePhase::Execution, $strict->phase);
        self::assertInstanceOf(\TypeError::class, $strict->cause);
    }

    public function testSupportsTopLevelReturnWithoutEndingTheHostingTest(): void
    {
        $result = (new InProcessExecutor())->execute(
            $this->transform("echo 'before'; return; echo 'after';"),
        );

        self::assertInstanceOf(ExecutionSucceeded::class, $result);
        self::assertSame('before', $result->stdout);
    }

    public function testReturnsAnAuthoredThrowableWithOutputCapturedBeforeFailure(): void
    {
        $prepared = $this->transform("echo 'before failure';\nthrow new RuntimeException('example failed');");

        $result = (new InProcessExecutor())->execute($prepared);

        self::assertInstanceOf(ExecutionFailed::class, $result);
        self::assertSame(FailurePhase::Execution, $result->phase);
        self::assertInstanceOf(\RuntimeException::class, $result->cause);
        self::assertSame('example failed', $result->cause->getMessage());
        self::assertSame('before failure', $result->stdout);
        self::assertSame([], $result->cleanupFailures);
        self::assertGreaterThanOrEqual(0, $result->durationNanoseconds);
        self::assertNotNull($result->generatedLine);
        self::assertSame($result->cause->getLine(), $result->generatedLine);
        self::assertSame(11, $prepared->sourceMap->sourceLineFor($result->generatedLine));
    }

    public function testCatchesAParseErrorFromMalformedPreparedSource(): void
    {
        $prepared = $this->rawPrepared("<?php\nnamespace Akashi\\Generated\\Malformed;\nif (", ExecutionMode::InProcess);

        $result = (new InProcessExecutor())->execute($prepared);

        self::assertInstanceOf(ExecutionFailed::class, $result);
        self::assertSame(FailurePhase::Execution, $result->phase);
        self::assertInstanceOf(\ParseError::class, $result->cause);
        self::assertSame([], $result->cleanupFailures);
        self::assertSame(3, $result->generatedLine);
    }

    public function testRecordsAFailureOnTheFirstGeneratedLine(): void
    {
        $prepared = $this->rawPrepared(
            "<?php throw new \\RuntimeException('first-line failure');",
            ExecutionMode::InProcess,
        );

        $result = (new InProcessExecutor())->execute($prepared);

        self::assertInstanceOf(ExecutionFailed::class, $result);
        self::assertSame(1, $result->generatedLine);
    }

    public function testRestoresWorkingDirectoryErrorReportingAndNestedOutput(): void
    {
        $initialWorkingDirectory = getcwd();
        $initialErrorReporting = error_reporting();
        self::assertIsString($initialWorkingDirectory);
        $source = <<<'PHP'
chdir(sys_get_temp_dir());
error_reporting(E_ERROR);
echo 'outer:';
ob_start(static fn (string $output): string => strtoupper($output));
echo 'nested';
PHP;

        $result = (new InProcessExecutor())->execute($this->transform($source));

        self::assertInstanceOf(ExecutionSucceeded::class, $result);
        self::assertSame('outer:NESTED', $result->stdout);
        self::assertSame($initialWorkingDirectory, getcwd());
        self::assertSame($initialErrorReporting, error_reporting());
    }

    #[DataProvider('outputBufferRestorationProvider')]
    public function testAlwaysRestoresTheSurroundingOutputBufferDepth(string $source): void
    {
        $initialOutputLevel = ob_get_level();

        try {
            (new InProcessExecutor())->execute($this->transform($source));
            $restoredOutputLevel = ob_get_level();
        } finally {
            while (ob_get_level() > $initialOutputLevel) {
                ob_end_clean();
            }
        }

        self::assertSame($initialOutputLevel, $restoredOutputLevel);
    }

    /** @return iterable<string, array{string}> */
    public static function outputBufferRestorationProvider(): iterable
    {
        yield 'no nested buffer' => ["echo 'plain';"];
        yield 'one nested buffer' => ["ob_start(); echo 'nested';"];
        yield 'multiple nested buffers' => ["ob_start(); echo 'outer'; ob_start(); echo 'inner';"];
        yield 'authored exception' => ["ob_start(); echo 'before'; throw new RuntimeException('failed');"];
        yield 'failed assertion' => ["ob_start(); echo 'before'; assert(false, 'failed');"];
    }

    public function testConfiguredExecutionUsesTheProjectRootAndBootstrapThenRestoresTheWorkingDirectory(): void
    {
        $workspace = $this->createWorkspace();
        $bootstrap = $workspace . '/bootstrap.php';
        self::assertNotFalse(file_put_contents(
            $bootstrap,
            "<?php\ndefine('AKASHI_IN_PROCESS_BOOTSTRAP_FIXTURE', 'loaded');\n",
        ));
        $configuration = RuntimeConfiguration::forProject($workspace)->withBootstrap('bootstrap.php');
        $initialWorkingDirectory = getcwd();
        self::assertIsString($initialWorkingDirectory);

        try {
            $result = (new InProcessExecutor($configuration))->execute($this->transform(
                "echo getcwd() . '|' . AKASHI_IN_PROCESS_BOOTSTRAP_FIXTURE;",
            ));

            self::assertInstanceOf(ExecutionSucceeded::class, $result);
            self::assertSame($workspace . '|loaded', $result->stdout);
            self::assertSame($initialWorkingDirectory, getcwd());
        } finally {
            self::assertTrue(unlink($bootstrap));
            self::assertTrue(rmdir($workspace));
        }
    }

    public function testThrowsAnInfrastructureFailureWhenTheConfiguredProjectDisappears(): void
    {
        $workspace = $this->createWorkspace();
        $configuration = RuntimeConfiguration::forProject($workspace);
        self::assertTrue(rmdir($workspace));
        $initialWorkingDirectory = getcwd();
        self::assertIsString($initialWorkingDirectory);

        try {
            (new InProcessExecutor($configuration))->execute($this->transform("echo 'not executed';"));
        } catch (ExecutionInfrastructureException $failure) {
            self::assertSame(
                'Unable to establish the configured in-process project root: '
                . $configuration->projectRoot->value
                . '.',
                $failure->getMessage(),
            );
            self::assertSame($initialWorkingDirectory, getcwd());

            return;
        }

        self::fail('A vanished in-process project root must be reported as an infrastructure error.');
    }

    public function testRejectsAnUnreadableConfiguredProjectRoot(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            self::markTestSkipped('POSIX directory permissions are unavailable on Windows.');
        }
        $workspace = $this->createWorkspace();
        $configuration = RuntimeConfiguration::forProject($workspace);
        self::assertTrue(chmod($workspace, 0o100));

        try {
            clearstatcache(true, $workspace);
            if (is_readable($workspace)) {
                self::markTestSkipped('The current user can read directories without permission bits.');
            }

            (new InProcessExecutor($configuration))->execute($this->transform("echo 'not executed';"));
        } catch (ExecutionInfrastructureException $failure) {
            self::assertSame(
                'Unable to establish the configured in-process project root: ' . $workspace . '.',
                $failure->getMessage(),
            );

            return;
        } finally {
            self::assertTrue(chmod($workspace, 0o700));
            self::assertTrue(rmdir($workspace));
        }

        self::fail('An unreadable in-process project root must be rejected before execution.');
    }

    public function testThrowsAnInfrastructureFailureWhenTheConfiguredBootstrapDisappears(): void
    {
        $workspace = $this->createWorkspace();
        $bootstrap = $workspace . '/bootstrap.php';
        self::assertNotFalse(file_put_contents($bootstrap, "<?php\n"));
        $configuration = RuntimeConfiguration::forProject($workspace)->withBootstrap('bootstrap.php');
        self::assertTrue(unlink($bootstrap));
        $initialWorkingDirectory = getcwd();
        self::assertIsString($initialWorkingDirectory);

        try {
            (new InProcessExecutor($configuration))->execute($this->transform("echo 'not executed';"));
        } catch (ExecutionInfrastructureException $failure) {
            self::assertSame(
                'Unable to load the configured in-process bootstrap: ' . $configuration->bootstrap?->value . '.',
                $failure->getMessage(),
            );
            self::assertSame($initialWorkingDirectory, getcwd());

            return;
        } finally {
            self::assertTrue(rmdir($workspace));
        }

        self::fail('A vanished in-process bootstrap must be reported as an infrastructure error.');
    }

    public function testThrowsAnInfrastructureFailureWhenTheConfiguredBootstrapBecomesADirectory(): void
    {
        $workspace = $this->createWorkspace();
        $bootstrap = $workspace . '/bootstrap.php';
        self::assertNotFalse(file_put_contents($bootstrap, "<?php\n"));
        $configuration = RuntimeConfiguration::forProject($workspace)->withBootstrap('bootstrap.php');
        self::assertTrue(unlink($bootstrap));
        self::assertTrue(mkdir($bootstrap));

        try {
            (new InProcessExecutor($configuration))->execute($this->transform("echo 'not executed';"));
        } catch (ExecutionInfrastructureException $failure) {
            self::assertSame(
                'Unable to load the configured in-process bootstrap: ' . $configuration->bootstrap?->value . '.',
                $failure->getMessage(),
            );

            return;
        } finally {
            self::assertTrue(rmdir($bootstrap));
            self::assertTrue(rmdir($workspace));
        }

        self::fail('An in-process bootstrap that became a directory must be rejected before loading.');
    }

    public function testWrapsAThrowableFromTheConfiguredBootstrapAndRestoresGuardedState(): void
    {
        $workspace = $this->createWorkspace();
        $bootstrap = $workspace . '/bootstrap.php';
        self::assertNotFalse(file_put_contents($bootstrap, <<<'PHP'
<?php

chdir(sys_get_temp_dir());
error_reporting(E_ERROR);
throw new RuntimeException('broken bootstrap');
PHP));
        $configuration = RuntimeConfiguration::forProject($workspace)->withBootstrap('bootstrap.php');
        $initialWorkingDirectory = getcwd();
        $initialErrorReporting = error_reporting();
        self::assertIsString($initialWorkingDirectory);

        try {
            (new InProcessExecutor($configuration))->execute($this->transform("echo 'not executed';"));
        } catch (ExecutionInfrastructureException $failure) {
            self::assertSame(
                'Configured in-process bootstrap failed: ' . $configuration->bootstrap?->value . '.',
                $failure->getMessage(),
            );
            self::assertSame(0, $failure->getCode());
            self::assertInstanceOf(\RuntimeException::class, $failure->getPrevious());
            self::assertSame('broken bootstrap', $failure->getPrevious()->getMessage());
            self::assertSame($initialWorkingDirectory, getcwd());
            self::assertSame($initialErrorReporting, error_reporting());

            return;
        } finally {
            self::assertTrue(unlink($bootstrap));
            self::assertTrue(rmdir($workspace));
        }

        self::fail('A throwable from an in-process bootstrap must be wrapped as an infrastructure error.');
    }

    public function testIsolatesRepeatedDeclarationsAcrossPreparedScopes(): void
    {
        $source = "class RepeatedDeclaration { public const VALUE = 'isolated'; } echo RepeatedDeclaration::VALUE;";
        $first = $this->transform($source);
        $second = $this->transform($source);
        $executor = new InProcessExecutor();

        $firstResult = $executor->execute($first);
        $secondResult = $executor->execute($second);

        self::assertNotSame($first->scope->namespace, $second->scope->namespace);
        self::assertInstanceOf(ExecutionSucceeded::class, $firstResult);
        self::assertInstanceOf(ExecutionSucceeded::class, $secondResult);
        self::assertSame('isolated', $firstResult->stdout);
        self::assertSame('isolated', $secondResult->stdout);
    }

    public function testExecutesARewrittenNativeAssertionExactlyOnce(): void
    {
        $result = (new InProcessExecutor())->execute(
            $this->transform('$evaluations = 0; assert(++$evaluations === 1); echo $evaluations;'),
        );

        self::assertInstanceOf(ExecutionSucceeded::class, $result);
        self::assertSame('1', $result->stdout);
    }

    public function testReturnsARewrittenNativeAssertionFailure(): void
    {
        $result = (new InProcessExecutor())->execute(
            $this->transform("echo 'before'; assert(false, 'documented failure');"),
        );

        self::assertInstanceOf(ExecutionFailed::class, $result);
        self::assertSame(FailurePhase::Execution, $result->phase);
        self::assertStringStartsWith('documented failure', $result->cause->getMessage());
        self::assertSame('before', $result->stdout);
        self::assertNotNull($result->generatedLine);
        self::assertSame(10, $result->preparedExample->sourceMap->sourceLineFor($result->generatedLine));
    }

    public function testPreservesAnAuthoredThrowableAssertionDescription(): void
    {
        $result = (new InProcessExecutor())->execute(
            $this->transform("assert(false, new DomainException('authored description'));"),
        );

        self::assertInstanceOf(ExecutionFailed::class, $result);
        self::assertInstanceOf(\DomainException::class, $result->cause);
        self::assertSame('authored description', $result->cause->getMessage());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testReturnsACleanupFailureWhenTheOwnedOutputBufferIsRemoved(): void
    {
        $prepared = $this->rawPrepared(
            "<?php\nnamespace Akashi\\Generated\\RemovedBuffer;\nob_end_clean();",
            ExecutionMode::InProcess,
        );

        $result = (new InProcessExecutor())->execute($prepared);

        self::assertInstanceOf(ExecutionFailed::class, $result);
        self::assertSame(FailurePhase::Cleanup, $result->phase);
        self::assertInstanceOf(ExecutionInfrastructureException::class, $result->cause);
        self::assertSame('', $result->stdout);
        self::assertCount(1, $result->cleanupFailures);
        self::assertSame(StateResource::OutputBuffer, $result->cleanupFailures[0]->resource);
        self::assertStringContainsString('owned by Akashi was removed', $result->cleanupFailures[0]->message);
        self::assertNull($result->generatedLine);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testPreservesExecutionFailureAsPrimaryWhenCleanupAlsoFails(): void
    {
        $prepared = $this->rawPrepared(
            "<?php\nnamespace Akashi\\Generated\\FailedRemovedBuffer;\nob_end_clean();\nthrow new \\RuntimeException('primary failure');",
            ExecutionMode::InProcess,
        );

        $result = (new InProcessExecutor())->execute($prepared);

        self::assertInstanceOf(ExecutionFailed::class, $result);
        self::assertSame(FailurePhase::Execution, $result->phase);
        self::assertInstanceOf(\RuntimeException::class, $result->cause);
        self::assertSame('primary failure', $result->cause->getMessage());
        self::assertCount(1, $result->cleanupFailures);
        self::assertSame(StateResource::OutputBuffer, $result->cleanupFailures[0]->resource);
        self::assertSame(4, $result->generatedLine);
    }

    public function testRejectsAPreparedExampleForAnotherExecutionMode(): void
    {
        $prepared = $this->rawPrepared("<?php\necho 'not executed';", ExecutionMode::SeparateProcess);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('accepts only in-process examples');

        (new InProcessExecutor())->execute($prepared);
    }

    private function transform(string $source): InProcessPreparedExample
    {
        ++$this->scopeSequence;

        return (new InProcessTransformer())->transform(
            $this->example($source),
            new ExecutionScope(sprintf('Akashi\\Generated\\ExecutorFixture_%d', $this->scopeSequence)),
        );
    }

    private function rawPrepared(string $source, ExecutionMode $mode): PreparedExample
    {
        $code = new PreparedCode($source);

        $example = $this->example('raw prepared fixture');
        $sourceMap = new SourceMap(
            new DocumentPath('docs/executor.md'),
            array_fill(0, $code->generatedLineCount(), null),
        );

        return match ($mode) {
            ExecutionMode::InProcess => new InProcessPreparedExample(
                $example,
                $code,
                $sourceMap,
                new ExecutionScope('Akashi\\Generated\\RawExecutorFixture'),
            ),
            ExecutionMode::SeparateProcess => new SeparateProcessPreparedExample($example, $code, $sourceMap),
        };
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

        return Example::fromInline(
            id: new ExampleId('example-executor-01'),
            label: 'In-process executor fixture',
            document: new Document('docs/executor.md', $source),
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

    private function createWorkspace(): string
    {
        $workspace = tempnam(sys_get_temp_dir(), 'akashi-in-process-');
        self::assertNotFalse($workspace);
        self::assertTrue(unlink($workspace));
        self::assertTrue(mkdir($workspace, 0o700));

        return $workspace;
    }
}
