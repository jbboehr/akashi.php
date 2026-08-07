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
use PHPUnit\Framework\ExpectationFailedException;

/** @implements Rule<Echo_> */
final class DocumentationEchoRule implements Rule
{
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

    protected function setUp(): void
    {
        parent::setUp();

        $projectRoot = tempnam(sys_get_temp_dir(), 'akashi-phpstan-verifier-');
        self::assertNotFalse($projectRoot);
        self::assertTrue(unlink($projectRoot));
        self::assertTrue(mkdir($projectRoot, 0o700));

        $this->projectRoot = $projectRoot;
        self::$recordedAnalysisPath = null;
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
        return new DocumentationEchoRule();
    }

    public static function recordAnalysisPath(string $path): void
    {
        self::$recordedAnalysisPath = $path;
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
                "<?php\n//! echo statements are forbidden\necho 'captured output';",
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

        return new Example(
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
