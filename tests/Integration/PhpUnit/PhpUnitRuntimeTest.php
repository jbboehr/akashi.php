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

namespace jbboehr\Akashi\Tests\Integration\PhpUnit;

use jbboehr\Akashi\Document;
use jbboehr\Akashi\Example;
use jbboehr\Akashi\Execution\Exception\ExecutionInfrastructureException;
use jbboehr\Akashi\Execution\Exception\RuntimeConfigurationException;
use jbboehr\Akashi\Execution\ExecutionMode;
use jbboehr\Akashi\Execution\RuntimeConfiguration;
use jbboehr\Akashi\Integration\PhpUnit\PhpUnitRuntime;
use jbboehr\Akashi\Model\Directive;
use jbboehr\Akashi\Model\DirectiveSet;
use jbboehr\Akashi\Model\ExampleCode;
use jbboehr\Akashi\Model\ExampleId;
use jbboehr\Akashi\Model\ExpectedException;
use jbboehr\Akashi\Model\FenceMetadata;
use jbboehr\Akashi\Model\Language;
use jbboehr\Akashi\Model\MetadataLocation;
use jbboehr\Akashi\Model\SourceLocation;
use jbboehr\Akashi\Model\SourceSpan;
use jbboehr\Akashi\Transform\Exception\UnsupportedExampleException;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class PhpUnitRuntimeTest extends TestCase
{
    private string $workspace;

    private bool $runtimeReturnRequired = false;

    protected function setUp(): void
    {
        $workspace = tempnam(sys_get_temp_dir(), 'akashi-phpunit-runtime-');
        self::assertNotFalse($workspace);
        self::assertTrue(unlink($workspace));
        self::assertTrue(mkdir($workspace, 0o700));

        $this->workspace = $workspace;
    }

    protected function tearDown(): void
    {
        $runtimeReturnRequired = $this->runtimeReturnRequired;
        $this->runtimeReturnRequired = false;

        if (is_dir($this->workspace)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->workspace, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST,
            );

            foreach ($iterator as $path) {
                if (!$path instanceof \SplFileInfo) {
                    continue;
                }

                if ($path->isDir() && !$path->isLink()) {
                    self::assertTrue(rmdir($path->getPathname()));
                } else {
                    self::assertTrue(unlink($path->getPathname()));
                }
            }

            self::assertTrue(rmdir($this->workspace));
        }

        self::assertFalse(
            $runtimeReturnRequired,
            'The ordinary runtime call must return instead of changing the PHPUnit test status.',
        );
    }

    public function testExecutesAnExampleAndRecordsOneCompletionAssertion(): void
    {
        $before = Assert::getCount();
        $this->runtimeReturnRequired = true;

        PhpUnitRuntime::assertExample($this->example("echo 'captured';"));

        $this->runtimeReturnRequired = false;
        $after = Assert::getCount();
        self::assertSame($before + 1, $after);
    }

    public function testIsolatesDuplicateDeclarationsAcrossFacadeCalls(): void
    {
        $before = Assert::getCount();

        PhpUnitRuntime::assertExample($this->example(
            "function shared_runtime_name(): string { return 'first'; }\nassert(shared_runtime_name() === 'first');",
            'example-runtime-01',
        ));
        PhpUnitRuntime::assertExample($this->example(
            "function shared_runtime_name(): string { return 'second'; }\nassert(shared_runtime_name() === 'second');",
            'example-runtime-02',
        ));

        $after = Assert::getCount();
        self::assertSame($before + 4, $after);
    }

    public function testReportsARewrittenAssertionAtTheMaintainedSourceLine(): void
    {
        try {
            PhpUnitRuntime::assertExample($this->example("echo 'before';\nassert(false, 'runtime failure');"));
        } catch (ExpectationFailedException $failure) {
            self::assertStringContainsString('Documentation example example-runtime-01 failed', $failure->getMessage());
            self::assertStringContainsString('Location: docs/runtime.md:11', $failure->getMessage());
            self::assertStringContainsString('runtime failure', $failure->getMessage());
            self::assertStringContainsString("Captured stdout:\n    before", $failure->getMessage());
            self::assertInstanceOf(ExpectationFailedException::class, $failure->getPrevious());

            return;
        }

        self::fail('A failing documentation assertion must fail the PHPUnit data set.');
    }

    public function testAcceptsAnAuthoredExpectedExceptionThroughTheFacade(): void
    {
        $before = Assert::getCount();

        PhpUnitRuntime::assertExample($this->example(
            "throw new RuntimeException('documented failure', 73);",
            expectedException: new ExpectedException(\Exception::class, 'documented', 73),
            expectedExceptionLine: 8,
        ));

        self::assertSame($before + 1, Assert::getCount());
    }

    #[DataProvider('separateProcessExpectationProvider')]
    public function testRejectsExpectedExceptionsForSeparateProcessExecution(bool $authoredDirective): void
    {
        $directives = $authoredDirective
            ? new DirectiveSet(Directive::SeparateProcess)
            : new DirectiveSet();
        $configuration = RuntimeConfiguration::forProject($this->workspace);
        if (!$authoredDirective) {
            $configuration = $configuration->withDefaultExecutionMode(ExecutionMode::SeparateProcess);
        }

        $this->expectException(UnsupportedExampleException::class);
        $this->expectExceptionMessage(
            'Example example-runtime-01 expects RuntimeException at docs/runtime.md:8, but expected exceptions '
                . 'currently require in-process execution.',
        );

        PhpUnitRuntime::assertExample(
            $this->example(
                "throw new RuntimeException('expected');",
                directives: $directives,
                directiveLine: $authoredDirective ? 7 : null,
                expectedException: new ExpectedException(\RuntimeException::class),
                expectedExceptionLine: 8,
            ),
            $configuration,
        );
    }

    /**
     * @param positive-int|null $directiveLine
     * @param positive-int $expectedLine
     */
    #[DataProvider('separateProcessLocationProvider')]
    public function testRequiresExplicitRuntimeConfigurationForASeparateProcessDirective(
        ?int $directiveLine,
        int $expectedLine,
    ): void {
        $example = $this->example(
            "throw new LogicException('must not execute');",
            directives: new DirectiveSet(Directive::SeparateProcess),
            directiveLine: $directiveLine,
        );

        $this->expectException(RuntimeConfigurationException::class);
        $this->expectExceptionMessage(sprintf(
            'Example example-runtime-01 at docs/runtime.md:%d requires RuntimeConfiguration with an explicit '
            . 'project root for separate-process execution.',
            $expectedLine,
        ));

        PhpUnitRuntime::assertExample($example);
    }

    public function testAnExplicitDirectiveOverridesTheInProcessDefaultAndUsesAnotherProcess(): void
    {
        $parentPid = getmypid();
        self::assertIsInt($parentPid);
        $example = $this->example(
            "file_put_contents('child.pid', (string) getmypid());",
            directives: new DirectiveSet(Directive::SeparateProcess),
            directiveLine: 8,
        );
        $configuration = RuntimeConfiguration::forProject($this->workspace);

        PhpUnitRuntime::assertExample($example, $configuration);

        $childPid = file_get_contents($this->workspace . '/child.pid');
        self::assertNotFalse($childPid);
        self::assertNotSame((string) $parentPid, $childPid);
    }

    public function testASkipDirectiveStopsBeforeConfigurationTransformationAndExecution(): void
    {
        $projectRoot = dirname(__DIR__, 3);
        $unexpectedFile = $this->workspace . '/unexpected.txt';
        $markdown = sprintf(
            "<!-- akashi: skip -->\n<!-- akashi: expect-exception LogicException -->\n"
                . "<!-- akashi: separate-process -->\n```php\n"
                . "file_put_contents(%s, 'executed');\nthrow new LogicException('executed');\n```\n",
            var_export($unexpectedFile, true),
        );
        self::assertNotFalse(file_put_contents($this->workspace . '/example.md', $markdown));
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/SkippedDocumentationExampleTest.php',
            <<<'PHP'
<?php

declare(strict_types=1);

use jbboehr\Akashi\Integration\PhpUnit\PhpUnitRuntime;
use jbboehr\Akashi\Source\MarkdownSource;
use PHPUnit\Framework\TestCase;

final class SkippedDocumentationExampleTest extends TestCase
{
    public function testSkippedDocumentationExample(): void
    {
        $corpus = MarkdownSource::forProject(__DIR__)
            ->includeFile('example.md')
            ->load();

        foreach ($corpus as $example) {
            PhpUnitRuntime::assertExample($example);
        }

        self::fail('A skipped documentation example must not return normally.');
    }
}
PHP,
        ));
        $process = new Process([
            PHP_BINARY,
            $projectRoot . '/vendor/bin/phpunit',
            '--no-configuration',
            '--bootstrap',
            $projectRoot . '/vendor/autoload.php',
            '--colors=never',
            '--display-skipped',
            $this->workspace . '/SkippedDocumentationExampleTest.php',
        ], $this->workspace);

        $process->run();

        $report = $process->getOutput() . $process->getErrorOutput();
        self::assertSame(0, $process->getExitCode(), $report);
        self::assertStringContainsString('Skipped: 1', $report);
        self::assertStringContainsString(
            '(example.md PHP example 1) at example.md:1 is marked to skip runtime execution.',
            $report,
        );
        self::assertFileDoesNotExist($unexpectedFile);
    }

    public function testTheConfiguredInProcessDefaultUsesTheProjectRoot(): void
    {
        $source = sprintf(
            "if (getcwd() !== %s) { throw new RuntimeException('wrong project root'); }",
            var_export($this->workspace, true),
        );

        PhpUnitRuntime::assertExample(
            $this->example($source),
            RuntimeConfiguration::forProject($this->workspace),
        );
    }

    public function testResolvesAnExpectedExceptionLoadedByTheRuntimeBootstrap(): void
    {
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/bootstrap.php',
            "<?php\nclass AkashiBootstrapExpectedException extends RuntimeException {}\n",
        ));
        $configuration = RuntimeConfiguration::forProject($this->workspace)->withBootstrap('bootstrap.php');

        PhpUnitRuntime::assertExample(
            $this->example(
                "throw new \\AkashiBootstrapExpectedException('expected');",
                expectedException: new ExpectedException('AkashiBootstrapExpectedException'),
                expectedExceptionLine: 8,
            ),
            $configuration,
        );
    }

    public function testPropagatesAnInProcessSetupFailureAsAnInfrastructureException(): void
    {
        $configuration = RuntimeConfiguration::forProject($this->workspace);
        self::assertTrue(rmdir($this->workspace));

        $this->expectException(ExecutionInfrastructureException::class);
        $this->expectExceptionMessage(
            'Unable to establish the configured in-process project root: '
            . $configuration->projectRoot->value
            . '.',
        );

        PhpUnitRuntime::assertExample(
            $this->example(
                "echo 'not executed';",
                expectedException: new ExpectedException(\LogicException::class),
                expectedExceptionLine: 8,
            ),
            $configuration,
        );
    }

    public function testTheConfiguredSeparateProcessDefaultUsesTheProjectRootAndBootstrap(): void
    {
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/bootstrap.php',
            "<?php\ndefine('AKASHI_SEPARATE_PROCESS_BOOTSTRAP_FIXTURE', 'loaded');\n",
        ));
        $configuration = RuntimeConfiguration::forProject($this->workspace)
            ->withBootstrap('bootstrap.php')
            ->withDefaultExecutionMode(ExecutionMode::SeparateProcess);
        $source = sprintf(
            "if (getcwd() !== %s || AKASHI_SEPARATE_PROCESS_BOOTSTRAP_FIXTURE !== 'loaded') { exit(19); }",
            var_export($this->workspace, true),
        );

        PhpUnitRuntime::assertExample($this->example($source), $configuration);
    }

    public function testReportsASeparateProcessFailureThroughTheCommonResultAsserter(): void
    {
        $example = $this->example(
            "file_put_contents('php://stderr', 'child failure'); exit(23);",
            directives: new DirectiveSet(Directive::SeparateProcess),
        );

        try {
            PhpUnitRuntime::assertExample($example, RuntimeConfiguration::forProject($this->workspace));
        } catch (ExpectationFailedException $failure) {
            self::assertStringContainsString('Documentation example example-runtime-01 failed', $failure->getMessage());
            self::assertStringContainsString('Separate PHP process exited with status 23', $failure->getMessage());
            self::assertStringContainsString("Captured stderr:\n    child failure", $failure->getMessage());

            return;
        }

        self::fail('A failing separate-process example must fail the PHPUnit data set.');
    }

    /** @return iterable<string, array{positive-int|null, positive-int}> */
    public static function separateProcessLocationProvider(): iterable
    {
        yield 'directive location' => [8, 8];
        yield 'example-start fallback' => [null, 10];
    }

    /** @return iterable<string, array{bool}> */
    public static function separateProcessExpectationProvider(): iterable
    {
        yield 'authored directive' => [true];
        yield 'configured default' => [false];
    }

    /**
     * @param non-empty-string $source
     * @param positive-int|null $directiveLine
     * @param positive-int|null $expectedExceptionLine
     */
    private function example(
        string $source,
        string $id = 'example-runtime-01',
        DirectiveSet $directives = new DirectiveSet(),
        ?int $directiveLine = null,
        ?ExpectedException $expectedException = null,
        ?int $expectedExceptionLine = null,
    ): Example {
        $lineBreaks = preg_match_all('/\r\n|\r|\n/', $source);
        if ($lineBreaks === false) {
            throw new \LogicException('Unable to count fixture source lines.');
        }

        $lineCount = $lineBreaks + 1;
        if (preg_match('/(?:\r\n|\r|\n)\z/', $source) === 1) {
            --$lineCount;
        }

        $sourceLength = strlen($source);
        $firstCodeLine = 10;
        $lastCodeLine = $firstCodeLine + $lineCount - 1;

        return Example::fromInline(
            id: new ExampleId($id),
            label: 'PHPUnit runtime fixture',
            document: new Document('docs/runtime.md', $source),
            location: new SourceLocation(
                $firstCodeLine - 1,
                $firstCodeLine,
                $lastCodeLine,
                $lastCodeLine + 1,
                new SourceSpan(0, $sourceLength),
                new SourceSpan(0, $sourceLength),
                new MetadataLocation(
                    separateProcessDirectiveLine: $directiveLine,
                    expectedExceptionDirectiveLine: $expectedExceptionLine,
                ),
            ),
            language: new Language('php'),
            code: new ExampleCode($source),
            fence: new FenceMetadata('php', '`', 3, 0),
            ordinal: 1,
            directives: $directives,
            expectedException: $expectedException,
        );
    }
}
