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

use jbboehr\Akashi\Document;
use jbboehr\Akashi\Example;
use jbboehr\Akashi\ExampleCorpus;
use jbboehr\Akashi\Integration\PHPStan\Exception\PhpStanVerificationException;
use jbboehr\Akashi\Integration\PHPStan\PhpStanDeclarationValidator;
use jbboehr\Akashi\Integration\PHPStan\PhpStanExampleConfiguration;
use jbboehr\Akashi\Integration\PHPStan\VerifiesPhpStanExamples;
use jbboehr\Akashi\Model\ExampleCode;
use jbboehr\Akashi\Model\ExampleId;
use jbboehr\Akashi\Model\FenceMetadata;
use jbboehr\Akashi\Model\Language;
use jbboehr\Akashi\Model\SourceLocation;
use jbboehr\Akashi\Model\SourceSpan;
use jbboehr\Akashi\Transform\PhpExampleParser;
use PhpParser\Node;
use PhpParser\Node\Stmt\Echo_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\ExpectationFailedException;

trait PhpStanHostCollisionTrait
{
}

final class PhpStanHostCollisionTraitUser
{
    use PhpStanHostCollisionTrait;
}

enum PhpStanHostCollisionEnum
{
    case Value;
}

/** @implements Rule<Echo_> */
final class DocumentationEchoRule implements Rule
{
    public function __construct(private readonly ?string $requiredClass = null)
    {
    }

    public function getNodeType(): string
    {
        return Echo_::class;
    }

    /**
     * @param Echo_ $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($this->requiredClass !== null && !class_exists($this->requiredClass, false)) {
            throw new \LogicException(sprintf(
                'Expected cross-file declaration %s to be loaded before analysis.',
                $this->requiredClass,
            ));
        }

        return [RuleErrorBuilder::message('echo statements are forbidden in analyzed documentation')
            ->identifier('akashi.echo')
            ->build()];
    }
}

/** @extends RuleTestCase<DocumentationEchoRule> */
final class VerifiesPhpStanExamplesTest extends RuleTestCase
{
    use VerifiesPhpStanExamples;

    private string $projectRoot;

    private static ?string $recordedAnalysisPath = null;

    private ?string $requiredClassDuringAnalysis = null;

    /** @var list<\PHPStan\Analyser\Error>|null */
    private ?array $controlledErrors = null;

    /** @var list<string> */
    private array $recordedAnalysisWorkingDirectories = [];

    protected function setUp(): void
    {
        parent::setUp();

        $projectRoot = tempnam(sys_get_temp_dir(), 'akashi-phpstan-verifier-');
        self::assertNotFalse($projectRoot);
        self::assertTrue(unlink($projectRoot));
        self::assertTrue(mkdir($projectRoot, 0o700));

        $this->projectRoot = $projectRoot;
        self::$recordedAnalysisPath = null;
        $this->controlledErrors = null;
        $this->recordedAnalysisWorkingDirectories = [];
    }

    protected function tearDown(): void
    {
        try {
            if (is_dir($this->projectRoot)) {
                self::assertTrue(rmdir($this->projectRoot));
            }
        } finally {
            parent::tearDown();
        }
    }

    protected function getRule(): Rule
    {
        return new DocumentationEchoRule($this->requiredClassDuringAnalysis);
    }

    public static function recordAnalysisPath(string $path): void
    {
        self::$recordedAnalysisPath = $path;
    }

    /**
     * @param array<string> $files
     *
     * @return list<\PHPStan\Analyser\Error>
     *
     * This controlled test seam follows PHPStan's RuleTestCase and serialized Error shape; review it on PHPStan updates.
     */
    public function gatherAnalyserErrors(array $files): array
    {
        if ($this->controlledErrors === null) {
            return parent::gatherAnalyserErrors($files);
        }

        $workingDirectory = getcwd();
        self::assertIsString($workingDirectory);
        $this->recordedAnalysisWorkingDirectories[] = $workingDirectory;
        self::assertTrue(chdir(sys_get_temp_dir()));

        return $this->controlledErrors;
    }

    public function testVerifiesRelevantExamplesAndCleansTheGuardedWorkspace(): void
    {
        $originalWorkingDirectory = getcwd();
        self::assertIsString($originalWorkingDirectory);
        $recordCall = sprintf('%s::recordAnalysisPath(__FILE__);', self::class);
        $corpus = new ExampleCorpus(
            $this->example(
                'example-a-01',
                'docs/a.md',
                1,
                "<?php\n// @akashi-phpstan-error akashi.echo\necho 'captured output';",
            ),
            $this->example('example-b-01', 'docs/b.md', 1, "<?php\n\\{$recordCall}"),
        );

        $this->assertPhpStanExamples(
            $corpus,
            PhpStanExampleConfiguration::forProject($this->projectRoot, static fn (Example $example): bool => true),
        );

        self::assertSame($originalWorkingDirectory, getcwd());
        self::assertNotNull(self::$recordedAnalysisPath);
        self::assertFileDoesNotExist(self::$recordedAnalysisPath);
        self::assertDirectoryDoesNotExist(dirname(self::$recordedAnalysisPath));
    }

    public function testMapsMismatchDiagnosticsBackToMaintainedMarkdownLines(): void
    {
        $example = $this->example(
            'example-mismatch-01',
            'docs/mismatch.md',
            1,
            "<?php\n//! a different diagnostic\necho 'captured';",
        );

        try {
            $this->assertPhpStanExamples(
                new ExampleCorpus($example),
                PhpStanExampleConfiguration::forProject(
                    $this->projectRoot,
                    static fn (Example $example): bool => true,
                ),
            );
        } catch (ExpectationFailedException $failure) {
            self::assertStringContainsString(
                'PHPStan diagnostics did not match documentation example example-mismatch-01.',
                $failure->getMessage(),
            );
            self::assertStringContainsString('Location: docs/mismatch.md:10', $failure->getMessage());
            self::assertStringContainsString('Mismatch: assignment', $failure->getMessage());
            self::assertStringContainsString('line 11: a different diagnostic', $failure->getMessage());
            self::assertStringContainsString(
                'source line 12 [akashi.echo]: echo statements are forbidden in analyzed documentation',
                $failure->getMessage(),
            );

            return;
        }

        self::fail('A mismatched PHPStan expectation must fail the PHPUnit assertion.');
    }

    public function testReportsIdentifierOnlyExpectationsInMismatchOutput(): void
    {
        $failure = $this->phpStanAssertionFailure($this->example(
            'example-identifier-mismatch-01',
            'docs/identifier-mismatch.md',
            1,
            "<?php\n// @akashi-phpstan-error expected.identifier\necho 'captured';",
        ));

        self::assertStringContainsString('line 11 [expected.identifier]', $failure->getMessage());
        self::assertStringContainsString('(statement line 12)', $failure->getMessage());
        self::assertStringNotContainsString('line 11 [expected.identifier]:', $failure->getMessage());
        self::assertStringContainsString('[akashi.echo]', $failure->getMessage());
    }

    public function testRequiresAnIdentifierDiagnosticOnItsAssociatedStatement(): void
    {
        $this->controlledErrors = [self::analyserError(
            'echo statements are forbidden in analyzed documentation',
            2,
            identifier: 'akashi.echo',
        )];

        $failure = $this->phpStanAssertionFailure($this->example(
            'example-identifier-line-mismatch-01',
            'docs/identifier-line-mismatch.md',
            1,
            "<?php\n// @akashi-phpstan-error akashi.echo\necho 'captured';",
        ));

        self::assertStringContainsString('line 11 [akashi.echo] (statement line 12)', $failure->getMessage());
        self::assertStringContainsString('source line 11 [akashi.echo]', $failure->getMessage());
    }

    public function testOrdersAndRetainsEveryReportedDiagnostic(): void
    {
        $this->controlledErrors = [
            self::analyserError('later diagnostic', 3, identifier: 'akashi.later'),
            self::analyserError('earlier diagnostic', 2, identifier: 'akashi.earlier'),
        ];

        $failure = $this->phpStanAssertionFailure($this->example(
            'example-diagnostic-order-01',
            'docs/diagnostic-order.md',
            1,
            "<?php\n\$first = 1;\n\$second = 2;",
        ));
        $message = $failure->getMessage();
        $earlier = strpos($message, 'source line 11 [akashi.earlier]: earlier diagnostic');
        $later = strpos($message, 'source line 12 [akashi.later]: later diagnostic');

        self::assertIsInt($earlier);
        self::assertIsInt($later);
        self::assertLessThan($later, $earlier);
        self::assertStringContainsString("Expected diagnostics:\n    (none)", $message);
    }

    public function testNormalizesUnavailableDiagnosticMetadata(): void
    {
        $this->controlledErrors = [
            self::analyserError('reported diagnostic', 0, tip: '   '),
        ];

        $failure = $this->phpStanAssertionFailure($this->example(
            'example-diagnostic-metadata-01',
            'docs/diagnostic-metadata.md',
            1,
            '<?php $value = 1;',
        ));

        self::assertStringContainsString('    - line unavailable: reported diagnostic', $failure->getMessage());
        self::assertStringNotContainsString('Tip:', $failure->getMessage());
    }

    public function testReportsAnOutOfRangeAnalyzerLineWithoutInventingASourceLine(): void
    {
        $this->controlledErrors = [self::analyserError('outside source', 999)];

        $failure = $this->phpStanAssertionFailure($this->example(
            'example-diagnostic-bounds-01',
            'docs/diagnostic-bounds.md',
            1,
            '<?php $value = 1;',
        ));

        self::assertStringContainsString('    - generated line 999: outside source', $failure->getMessage());
    }

    public function testRejectsAWhitespaceOnlyPhpStanMessage(): void
    {
        $this->controlledErrors = [self::analyserError('   ', 1)];

        $this->expectException(PhpStanVerificationException::class);
        $this->expectExceptionMessage('PHPStan returned an empty diagnostic message');

        $this->assertControlledPhpStanExamples($this->example(
            'example-empty-diagnostic-01',
            'docs/empty-diagnostic.md',
            1,
            '<?php $value = 1;',
        ));
    }

    public function testReportsWhenAnExpectedDiagnosticIsMissing(): void
    {
        $this->controlledErrors = [];

        $failure = $this->phpStanAssertionFailure($this->example(
            'example-missing-diagnostic-01',
            'docs/missing-diagnostic.md',
            1,
            "<?php\n//! expected diagnostic\n\$value = 1;",
        ));

        self::assertStringContainsString("Reported diagnostics:\n    (none)", $failure->getMessage());
    }

    public function testReestablishesTheProjectRootBeforeLoadingAndBeforeEveryAnalysis(): void
    {
        $this->controlledErrors = [];
        $expectedRoot = var_export($this->projectRoot, true);
        $first = $this->example(
            'example-project-root-a-01',
            'docs/project-root-a.md',
            1,
            sprintf(
                "<?php\nif (getcwd() !== %s) { throw new RuntimeException('wrong load root'); }",
                $expectedRoot,
            ),
        );
        $second = $this->example(
            'example-project-root-b-01',
            'docs/project-root-b.md',
            1,
            '<?php $value = 1;',
        );

        $this->assertControlledPhpStanExamples($first, $second);

        self::assertSame([$this->projectRoot, $this->projectRoot], $this->recordedAnalysisWorkingDirectories);
    }

    public function testAddsAnOpeningTagWithoutLosingSourceLineMapping(): void
    {
        $this->assertPhpStanExamples(
            new ExampleCorpus($this->example(
                'example-tagless-01',
                'docs/tagless.md',
                1,
                "//! echo statements are forbidden\necho 'captured';",
            )),
            PhpStanExampleConfiguration::forProject($this->projectRoot, static fn (Example $example): bool => true),
        );
    }

    public function testLoadsTheWholeCorpusBeforeAnalyzingCrossFileDeclarations(): void
    {
        $class = 'Akashi\\PhpStanCrossFileFixture\\LaterDeclaration';
        $this->requiredClassDuringAnalysis = $class;

        $this->assertPhpStanExamples(
            new ExampleCorpus(
                $this->example(
                    'example-analysis-first-01',
                    'docs/analysis-first.md',
                    1,
                    "<?php\n//! echo statements are forbidden\necho 'analyze after loading';",
                ),
                $this->example(
                    'example-declaration-later-01',
                    'docs/declaration-later.md',
                    1,
                    "<?php\nnamespace Akashi\\PhpStanCrossFileFixture;\nfinal class LaterDeclaration {}",
                ),
            ),
            PhpStanExampleConfiguration::forProject(
                $this->projectRoot,
                static fn (Example $example): bool => true,
            ),
        );

        self::assertTrue(class_exists($class, false));
    }

    public function testCleanupFailurePreservesTheOriginalLoadingFailure(): void
    {
        $recordCall = sprintf('%s::recordAnalysisPath(__FILE__);', self::class);
        $failure = null;

        try {
            $this->assertPhpStanExamples(
                new ExampleCorpus($this->example(
                    'example-cleanup-failure-01',
                    'docs/cleanup-failure.md',
                    1,
                    sprintf(
                        "<?php\n\\%s\nfile_put_contents(__DIR__ . '/leftover', 'owned');\n"
                        . "throw new RuntimeException('original loading failure');",
                        $recordCall,
                    ),
                )),
                PhpStanExampleConfiguration::forProject(
                    $this->projectRoot,
                    static fn (Example $example): bool => true,
                ),
            );
        } catch (PhpStanVerificationException $caught) {
            $failure = $caught;
        } finally {
            $analysisPath = self::$recordedAnalysisPath;
            if ($analysisPath !== null) {
                $analysisDirectory = dirname($analysisPath);
                $leftover = $analysisDirectory . '/leftover';
                if (file_exists($leftover)) {
                    self::assertTrue(unlink($leftover));
                }
                if (is_dir($analysisDirectory)) {
                    self::assertTrue(rmdir($analysisDirectory));
                }
            }
        }

        self::assertNotNull($failure);
        self::assertStringContainsString(
            'PHPStan example verification cleanup failed:',
            $failure->getMessage(),
        );
        self::assertStringContainsString(
            'unable to remove temporary analysis directory:',
            $failure->getMessage(),
        );

        $originalFailure = $failure->getPrevious();
        self::assertInstanceOf(PhpStanVerificationException::class, $originalFailure);
        self::assertStringContainsString(
            'Unable to load PHPStan example example-cleanup-failure-01 at docs/cleanup-failure.md:10: '
            . 'RuntimeException: original loading failure',
            $originalFailure->getMessage(),
        );
    }

    public function testReportsGuardedOutputBufferCleanupFailure(): void
    {
        $this->controlledErrors = [];
        $initialOutputLevel = ob_get_level();

        try {
            $this->assertControlledPhpStanExamples($this->example(
                'example-output-cleanup-01',
                'docs/output-cleanup.md',
                1,
                "<?php\nob_end_clean();",
            ));
        } catch (PhpStanVerificationException $failure) {
            self::assertStringContainsString('PHPStan example verification cleanup failed:', $failure->getMessage());
            self::assertStringContainsString(
                'output-buffer: The output buffer owned by Akashi was removed during execution.',
                $failure->getMessage(),
            );
            self::assertSame($initialOutputLevel, ob_get_level());

            return;
        }

        self::fail('Removing the verifier output buffer must be reported as a cleanup failure.');
    }

    public function testRemovesABrokenSymlinkThatReplacesAnAnalysisFile(): void
    {
        if (DIRECTORY_SEPARATOR === '\\' || !function_exists('symlink')) {
            self::markTestSkipped('Replacing an analysis file with a symbolic link is unavailable.');
        }
        $this->controlledErrors = [];
        $recordCall = sprintf('%s::recordAnalysisPath(__FILE__);', self::class);

        try {
            $this->assertControlledPhpStanExamples($this->example(
                'example-analysis-symlink-01',
                'docs/analysis-symlink.md',
                1,
                sprintf(
                    "<?php\n\\%s\n\$path = __FILE__; unlink(\$path); symlink(\$path . '.missing', \$path);",
                    $recordCall,
                ),
            ));

            self::assertNotNull(self::$recordedAnalysisPath);
            self::assertFalse(is_link(self::$recordedAnalysisPath));
        } finally {
            $analysisPath = self::$recordedAnalysisPath;
            if ($analysisPath !== null && is_link($analysisPath)) {
                self::assertTrue(unlink($analysisPath));
            }
            if ($analysisPath !== null && is_dir(dirname($analysisPath))) {
                self::assertTrue(rmdir(dirname($analysisPath)));
            }
        }
    }

    public function testReportsEveryCleanupFailureWhenAnAnalysisFileBecomesADirectory(): void
    {
        $this->controlledErrors = [];
        $recordCall = sprintf('%s::recordAnalysisPath(__FILE__);', self::class);
        $failure = null;

        try {
            $this->assertControlledPhpStanExamples($this->example(
                'example-analysis-directory-01',
                'docs/analysis-directory.md',
                1,
                sprintf(
                    "<?php\n\\%s\n\$path = __FILE__; unlink(\$path); mkdir(\$path);",
                    $recordCall,
                ),
            ));
        } catch (PhpStanVerificationException $caught) {
            $failure = $caught;
        } finally {
            $analysisPath = self::$recordedAnalysisPath;
            if ($analysisPath !== null && is_dir($analysisPath)) {
                self::assertTrue(rmdir($analysisPath));
            }
            if ($analysisPath !== null && is_dir(dirname($analysisPath))) {
                self::assertTrue(rmdir(dirname($analysisPath)));
            }
        }

        self::assertNotNull($failure);
        self::assertStringContainsString('temporary file path became a directory:', $failure->getMessage());
        self::assertStringContainsString('unable to remove temporary analysis directory:', $failure->getMessage());
    }

    public function testRejectsProcessTerminationBeforeLoadingAnyExample(): void
    {
        $this->expectException(PhpStanVerificationException::class);
        $this->expectExceptionMessage(
            'Unsafe PHPStan example example-exit-01 at docs/exit.md:11: '
            . 'exit and die can terminate the hosting PHPStan test process.',
        );

        $this->assertPhpStanExamples(
            new ExampleCorpus($this->example('example-exit-01', 'docs/exit.md', 1, "<?php\nexit(1);")),
            PhpStanExampleConfiguration::forProject($this->projectRoot, static fn (Example $example): bool => true),
        );
    }

    public function testRejectsDuplicateDeclarationsBeforeEitherFileIsLoaded(): void
    {
        $name = 'akashi_phpstan_duplicate_fixture';

        try {
            $this->assertPhpStanExamples(
                new ExampleCorpus(
                    $this->example('example-a-01', 'docs/a.md', 1, "<?php\nfunction {$name}(): void {}"),
                    $this->example('example-b-01', 'docs/b.md', 1, "<?php\nfunction {$name}(): void {}"),
                ),
                PhpStanExampleConfiguration::forProject(
                    $this->projectRoot,
                    static fn (Example $example): bool => true,
                ),
            );
        } catch (PhpStanVerificationException $failure) {
            self::assertStringContainsString(
                "duplicate function declaration {$name} already authored by example example-a-01 at docs/a.md:10",
                $failure->getMessage(),
            );
            self::assertFalse(function_exists($name));

            return;
        }

        self::fail('Duplicate PHPStan example declarations must be rejected before loading.');
    }

    public function testRejectsDuplicateClassLikeDeclarationsBeforeLoading(): void
    {
        $this->expectException(PhpStanVerificationException::class);
        $this->expectExceptionMessage('duplicate class-like declaration Akashi\\DuplicateFixture\\Repeated');

        $this->validateExamples(
            $this->example(
                'example-class-a-01',
                'docs/class-a.md',
                1,
                '<?php namespace Akashi\\DuplicateFixture; class Repeated {}',
            ),
            $this->example(
                'example-class-b-01',
                'docs/class-b.md',
                1,
                '<?php namespace Akashi\\DuplicateFixture; class Repeated {}',
            ),
        );
    }

    public function testRejectsDuplicateGlobalConstantsBeforeLoading(): void
    {
        $this->expectException(PhpStanVerificationException::class);
        $this->expectExceptionMessage('duplicate constant declaration AKASHI_PHPSTAN_DUPLICATE_CONSTANT');

        $this->validateExamples(
            $this->example(
                'example-constant-a-01',
                'docs/constant-a.md',
                1,
                '<?php const AKASHI_PHPSTAN_DUPLICATE_CONSTANT = 1;',
            ),
            $this->example(
                'example-constant-b-01',
                'docs/constant-b.md',
                1,
                '<?php const AKASHI_PHPSTAN_DUPLICATE_CONSTANT = 2;',
            ),
        );
    }

    #[DataProvider('loadedClassLikeDeclarationProvider')]
    public function testRejectsEveryKindOfClassLikeDeclarationAlreadyLoadedByTheHost(
        string $source,
        string $name,
    ): void {
        $this->expectException(PhpStanVerificationException::class);
        $this->expectExceptionMessage("class-like declaration {$name} already exists in the hosting process");

        $this->validateExamples($this->example(
            'example-loaded-class-like-01',
            'docs/loaded-class-like.md',
            1,
            $source,
        ));
    }

    /** @return iterable<string, array{string, class-string}> */
    public static function loadedClassLikeDeclarationProvider(): iterable
    {
        yield 'class' => ['<?php class stdClass {}', \stdClass::class];
        yield 'interface' => ['<?php interface Stringable {}', \Stringable::class];
        yield 'trait' => [
            '<?php namespace jbboehr\\Akashi\\Tests\\Integration\\PHPStan; trait PhpStanHostCollisionTrait {}',
            PhpStanHostCollisionTrait::class,
        ];
        yield 'enum' => [
            '<?php namespace jbboehr\\Akashi\\Tests\\Integration\\PHPStan; enum PhpStanHostCollisionEnum {}',
            PhpStanHostCollisionEnum::class,
        ];
    }

    public function testDeclarationPreflightDoesNotInvokeAutoloaders(): void
    {
        $autoloaded = [];
        $autoloader = static function (string $class) use (&$autoloaded): void {
            $autoloaded[] = $class;
        };
        spl_autoload_register($autoloader);

        try {
            $this->validateExamples($this->example(
                'example-no-autoload-01',
                'docs/no-autoload.md',
                1,
                <<<'PHP'
<?php
namespace Akashi\NoAutoloadFixture;
class LocalClass {}
interface LocalInterface {}
trait LocalTrait {}
enum LocalEnum {}
PHP,
            ));
        } finally {
            spl_autoload_unregister($autoloader);
        }

        self::assertSame([], $autoloaded);
    }

    public function testAllowsClassConstantsDuringDeclarationPreflight(): void
    {
        $this->validateExamples($this->example(
            'example-class-constant-01',
            'docs/class-constant.md',
            1,
            "<?php\nnamespace Akashi\\ClassConstantFixture;\nfinal class Example { public const VALUE = 1; }",
        ));

        self::addToAssertionCount(1);
    }

    public function testRejectsADeclarationAlreadyLoadedByTheHost(): void
    {
        $this->expectException(PhpStanVerificationException::class);
        $this->expectExceptionMessage(
            'Unsafe PHPStan example example-host-collision-01 at docs/host-collision.md:11: '
            . 'function declaration strlen already exists in the hosting process.',
        );

        $this->validateExamples($this->example(
            'example-host-collision-01',
            'docs/host-collision.md',
            1,
            "<?php\nfunction strlen(string \$value): int { return 0; }",
        ));
    }

    public function testRejectsHaltCompilerBeforeLoadingTheExample(): void
    {
        $this->expectException(PhpStanVerificationException::class);
        $this->expectExceptionMessage(
            'Unsafe PHPStan example example-halt-01 at docs/halt.md:11: '
            . '__halt_compiler() can terminate parsing of the generated analysis file.',
        );

        $this->validateExamples($this->example(
            'example-halt-01',
            'docs/halt.md',
            1,
            "<?php\n__halt_compiler();",
        ));
    }

    public function testRejectsBuiltInDefineBeforeItCanPolluteTheHost(): void
    {
        $constant = 'AKASHI_PHPSTAN_DEFINE_FIXTURE';

        try {
            $this->validateExamples($this->example(
                'example-define-01',
                'docs/define.md',
                1,
                "<?php\nnamespace Akashi\\DefineFixture;\ndefine('{$constant}', 1);",
            ));
        } catch (PhpStanVerificationException $failure) {
            self::assertStringContainsString(
                'built-in define() creates persistent process state that cannot be reversed after analysis',
                $failure->getMessage(),
            );
            self::assertFalse(defined($constant));

            return;
        }

        self::fail('Built-in define() must be rejected before loading the example.');
    }

    public function testAllowsAnExplicitNamespacedFunctionNamedDefine(): void
    {
        $this->validateExamples($this->example(
            'example-local-define-01',
            'docs/local-define.md',
            1,
            <<<'PHP'
<?php
namespace Akashi\LocalDefineFixture;
function define(string $name, mixed $value): void {}
define('local-only', 1);
PHP,
        ));

        self::addToAssertionCount(1);
    }

    public function testRejectsAFullyQualifiedUppercaseBuiltInDefineCall(): void
    {
        $constant = 'AKASHI_PHPSTAN_QUALIFIED_DEFINE_FIXTURE';

        try {
            $this->validateExamples($this->example(
                'example-qualified-define-01',
                'docs/qualified-define.md',
                1,
                "<?php\n\\DEFINE('{$constant}', 1);",
            ));
        } catch (PhpStanVerificationException $failure) {
            self::assertStringContainsString('built-in define() creates persistent process state', $failure->getMessage());
            self::assertFalse(defined($constant));

            return;
        }

        self::fail('A fully qualified built-in define() call must be rejected before loading.');
    }

    private function assertControlledPhpStanExamples(Example ...$examples): void
    {
        $this->assertPhpStanExamples(
            new ExampleCorpus(...$examples),
            PhpStanExampleConfiguration::forProject(
                $this->projectRoot,
                static fn (Example $example): bool => true,
            ),
        );
    }

    private function phpStanAssertionFailure(Example $example): ExpectationFailedException
    {
        try {
            $this->assertControlledPhpStanExamples($example);
        } catch (ExpectationFailedException $failure) {
            return $failure;
        }

        self::fail('A PHPStan diagnostic mismatch must fail the PHPUnit assertion.');
    }

    private static function analyserError(
        string $message,
        ?int $line,
        ?string $tip = null,
        ?string $identifier = null,
    ): \PHPStan\Analyser\Error {
        return \PHPStan\Analyser\Error::decode([
            'message' => $message,
            'file' => 'analysis.php',
            'line' => $line,
            'canBeIgnored' => true,
            'filePath' => null,
            'traitFilePath' => null,
            'tip' => $tip,
            'nodeLine' => null,
            'nodeType' => null,
            'identifier' => $identifier,
            'metadata' => [],
            'fixedErrorDiffHash' => null,
            'fixedErrorDiffDiff' => null,
        ]);
    }

    private function validateExamples(Example ...$examples): void
    {
        $parser = new PhpExampleParser();
        $parsed = [];
        foreach ($examples as $example) {
            $parsed[] = ['example' => $example, 'parsed' => $parser->parse($example)];
        }

        (new PhpStanDeclarationValidator())->validate($parsed);
    }

    /** @param positive-int $ordinal */
    private function example(string $id, string $path, int $ordinal, string $source): Example
    {
        $lineBreaks = preg_match_all('/\r\n|\r|\n/', $source);
        self::assertNotFalse($lineBreaks);
        $lastCodeLine = 10 + $lineBreaks;
        $sourceLength = strlen($source);

        return Example::fromInline(
            id: new ExampleId($id),
            label: $path . ' PHP example ' . $ordinal,
            document: new Document($path, $source),
            location: new SourceLocation(
                9,
                10,
                $lastCodeLine,
                $lastCodeLine + 1,
                new SourceSpan(0, $sourceLength),
                new SourceSpan(0, $sourceLength),
            ),
            language: new Language('php'),
            code: new ExampleCode($source),
            fence: new FenceMetadata('php', '`', 3, 0),
            ordinal: $ordinal,
        );
    }
}
