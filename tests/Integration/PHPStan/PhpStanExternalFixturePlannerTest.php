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

use jbboehr\Akashi\Integration\PHPStan\DiagnosticExpectation;
use jbboehr\Akashi\Integration\PHPStan\Exception\ExpectationParseException;
use jbboehr\Akashi\Integration\PHPStan\Exception\NoRelevantExamplesException;
use jbboehr\Akashi\Integration\PHPStan\Exception\PhpStanConfigurationException;
use jbboehr\Akashi\Integration\PHPStan\PhpStanExampleConfiguration;
use jbboehr\Akashi\Integration\PHPStan\PhpStanExternalFixturePlan;
use jbboehr\Akashi\Integration\PHPStan\PhpStanExternalFixturePlanner;
use jbboehr\Akashi\Model\ProjectRoot;
use jbboehr\Akashi\Source\DocumentationSource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PhpStanExternalFixturePlannerTest extends TestCase
{
    private string $workspace;

    protected function setUp(): void
    {
        $workspace = tempnam(sys_get_temp_dir(), 'akashi-external-phpstan-');
        self::assertNotFalse($workspace);
        self::assertTrue(unlink($workspace));
        self::assertTrue(mkdir($workspace, 0o700));
        self::assertTrue(mkdir($workspace . '/examples'));
        self::assertTrue(mkdir($workspace . '/src'));

        $canonicalWorkspace = realpath($workspace);
        self::assertNotFalse($canonicalWorkspace);
        $this->workspace = str_replace('\\', '/', $canonicalWorkspace);
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

    public function testGroupsWholeFileAndNamedRegionsIntoOneCanonicalAnalysis(): void
    {
        $fixture = <<<'PHP'
            <?php

            // akashi-region: clean
            // @akashi-phpstan-example
            // @akashi-phpstan-error external.first
            $clean = 1;
            // akashi-region-end: clean

            // akashi-region: diagnostic
            // @akashi-phpstan-example
            // @akashi-phpstan-error external.expected: expected external diagnostic
            missingExternalFixtureFunction();
            // akashi-region-end: diagnostic
            PHP;
        $documentation = <<<'PHP'
            <?php

            /**
             * @akashi-example examples/fixture.php
             * @akashi-example examples/fixture.php#clean
             * @akashi-example examples/fixture.php#diagnostic
             * @akashi-example examples/fixture.php#diagnostic
             */
            final class ExternalFixtureDocumentation
            {
            }
            PHP;
        self::assertNotFalse(file_put_contents($this->workspace . '/examples/fixture.php', $fixture));
        self::assertNotFalse(file_put_contents($this->workspace . '/src/Documentation.php', $documentation));

        $corpus = DocumentationSource::forProject($this->workspace)
            ->withFile('src/Documentation.php')
            ->load();
        $configuration = PhpStanExampleConfiguration::forTokens(
            $this->workspace,
            '@akashi-phpstan-example',
        );

        $plan = (new PhpStanExternalFixturePlanner())->plan($corpus, $configuration);

        self::assertSame($this->workspace, $plan->projectRoot->value);
        self::assertSame(['examples/fixture.php'], $plan->analysisPaths);
        $fixturePath = $this->nativePath('examples/fixture.php');
        self::assertSame([$fixturePath], array_keys($plan->expectationsByFile));
        $expectations = $plan->expectationsByFile[$fixturePath];
        self::assertCount(2, $expectations);
        self::assertNull($expectations[0]->text);
        self::assertSame('external.first', $expectations[0]->identifier);
        self::assertSame(5, $expectations[0]->sourceLine);
        self::assertSame(['first' => 6, 'last' => 6], $expectations[0]->sourceLineRange);
        self::assertSame('expected external diagnostic', $expectations[1]->text);
        self::assertSame('external.expected', $expectations[1]->identifier);
        self::assertSame(11, $expectations[1]->sourceLine);
        self::assertSame(['first' => 12, 'last' => 12], $expectations[1]->sourceLineRange);
    }

    public function testParsesAContextDependentNamedRegionAgainstItsCanonicalFile(): void
    {
        $fixture = <<<'PHP'
            <?php
            final class ContextualFixture
            {
                // akashi-region: property
                // @akashi-phpstan-example
                // @akashi-phpstan-error assign.propertyType
                private int $value = 'invalid';
                // akashi-region-end: property
            }
            PHP;
        $documentation = <<<'PHP'
            <?php

            /** @akashi-example examples/contextual.php#property */
            final class ContextualFixtureDocumentation
            {
            }
            PHP;
        self::assertNotFalse(file_put_contents($this->workspace . '/examples/contextual.php', $fixture));
        self::assertNotFalse(file_put_contents($this->workspace . '/src/Documentation.php', $documentation));

        $plan = (new PhpStanExternalFixturePlanner())->plan(
            DocumentationSource::forProject($this->workspace)
                ->withFile('src/Documentation.php')
                ->load(),
            PhpStanExampleConfiguration::forTokens($this->workspace, '@akashi-phpstan-example'),
        );

        self::assertSame(['examples/contextual.php'], $plan->analysisPaths);
        $expectations = $plan->expectationsByFile[$this->nativePath('examples/contextual.php')];
        self::assertCount(1, $expectations);
        self::assertSame('assign.propertyType', $expectations[0]->identifier);
        self::assertSame(6, $expectations[0]->sourceLine);
        self::assertSame(['first' => 7, 'last' => 7], $expectations[0]->sourceLineRange);
    }

    public function testGroupsNamedRegionsReferencedThroughHardLinkAliases(): void
    {
        $fixture = <<<'PHP'
            <?php

            // akashi-region: first
            //! first hard-link diagnostic
            $first = 1;
            // akashi-region-end: first

            // akashi-region: second
            //! second hard-link diagnostic
            $second = 2;
            // akashi-region-end: second
            PHP;
        $firstPath = $this->workspace . '/examples/a-fixture.php';
        $secondPath = $this->workspace . '/examples/z-fixture.php';
        self::assertNotFalse(file_put_contents($secondPath, $fixture));
        if (!@link($secondPath, $firstPath)) {
            self::markTestSkipped('The current filesystem does not permit hard-link fixture aliases.');
        }
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/src/Documentation.php',
            <<<'PHP'
                <?php

                /**
                 * @akashi-example examples/a-fixture.php#first
                 * @akashi-example examples/z-fixture.php#second
                 */
                final class HardLinkExternalFixtureDocumentation
                {
                }
                PHP,
        ));

        $plan = (new PhpStanExternalFixturePlanner())->plan(
            DocumentationSource::forProject($this->workspace)
                ->withFile('src/Documentation.php')
                ->load(),
            PhpStanExampleConfiguration::forTokens($this->workspace, '//!'),
        );

        self::assertSame(['examples/a-fixture.php'], $plan->analysisPaths);
        $expectations = $plan->expectationsByFile[$this->nativePath('examples/a-fixture.php')];
        self::assertSame(
            ['first hard-link diagnostic', 'second hard-link diagnostic'],
            array_map(
                static fn (DiagnosticExpectation $expectation): ?string => $expectation->text,
                $expectations,
            ),
        );
    }

    public function testRetainsAnEmptyExpectationListForASelectedCleanFile(): void
    {
        $fixture = <<<'PHP'
            <?php

            // @akashi-phpstan-example
            $clean = 1;
            PHP;
        $documentation = <<<'PHP'
            <?php

            /** @akashi-example examples/fixture.php */
            final class CleanExternalFixtureDocumentation
            {
            }
            PHP;
        self::assertNotFalse(file_put_contents($this->workspace . '/examples/fixture.php', $fixture));
        self::assertNotFalse(file_put_contents($this->workspace . '/src/Documentation.php', $documentation));

        $plan = (new PhpStanExternalFixturePlanner())->plan(
            DocumentationSource::forProject($this->workspace)
                ->withFile('src/Documentation.php')
                ->load(),
            PhpStanExampleConfiguration::forTokens($this->workspace, '@akashi-phpstan-example'),
        );

        self::assertSame([], $plan->expectationsByFile[$this->nativePath('examples/fixture.php')]);
    }

    public function testKeepsDistinctCanonicalFilesAndTheirExpectationsSeparate(): void
    {
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/examples/z.php',
            "<?php\n// @akashi-phpstan-example\n//! z diagnostic\n\$z = 1;\n",
        ));
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/examples/a.php',
            "<?php\n// @akashi-phpstan-example\n//! a diagnostic\n\$a = 1;\n",
        ));
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/src/Documentation.php',
            <<<'PHP'
                <?php

                /**
                 * @akashi-example examples/z.php
                 * @akashi-example examples/a.php
                 */
                final class MultipleExternalFixtureDocumentation
                {
                }
                PHP,
        ));

        $plan = (new PhpStanExternalFixturePlanner())->plan(
            DocumentationSource::forProject($this->workspace)
                ->withFile('src/Documentation.php')
                ->load(),
            PhpStanExampleConfiguration::forTokens($this->workspace, '@akashi-phpstan-example'),
        );

        self::assertSame(['examples/a.php', 'examples/z.php'], $plan->analysisPaths);
        self::assertSame(
            ['a diagnostic'],
            array_map(
                static fn (DiagnosticExpectation $expectation): ?string => $expectation->text,
                $plan->expectationsByFile[$this->nativePath('examples/a.php')],
            ),
        );
        self::assertSame(
            ['z diagnostic'],
            array_map(
                static fn (DiagnosticExpectation $expectation): ?string => $expectation->text,
                $plan->expectationsByFile[$this->nativePath('examples/z.php')],
            ),
        );
    }

    public function testRejectsASelectedInlineExample(): void
    {
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/README.md',
            "```php\n// @akashi-phpstan-example\n\$value = 1;\n```\n",
        ));

        $this->expectException(PhpStanConfigurationException::class);
        $this->expectExceptionMessage('includes inline example');
        (new PhpStanExternalFixturePlanner())->plan(
            DocumentationSource::forProject($this->workspace)->withFile('README.md')->load(),
            PhpStanExampleConfiguration::forTokens($this->workspace, '@akashi-phpstan-example'),
        );
    }

    public function testPropagatesAnEmptyDiagnosticExpectation(): void
    {
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/examples/fixture.php',
            "<?php\n// @akashi-phpstan-example\n//!\n\$value = 1;\n",
        ));
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/src/Documentation.php',
            "<?php\n/** @akashi-example examples/fixture.php */\nfinal class EmptyExternalFixtureDocumentation {}\n",
        ));

        $this->expectException(ExpectationParseException::class);
        $this->expectExceptionMessage('contains an empty PHPStan diagnostic expectation');
        (new PhpStanExternalFixturePlanner())->plan(
            DocumentationSource::forProject($this->workspace)
                ->withFile('src/Documentation.php')
                ->load(),
            PhpStanExampleConfiguration::forTokens($this->workspace, '@akashi-phpstan-example'),
        );
    }

    public function testRejectsAFileThatChangedAfterCorpusLoading(): void
    {
        $fixturePath = $this->workspace . '/examples/fixture.php';
        self::assertNotFalse(file_put_contents(
            $fixturePath,
            "<?php\n// @akashi-phpstan-example\n\$value = 1;\n",
        ));
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/src/Documentation.php',
            "<?php\n/** @akashi-example examples/fixture.php */\nfinal class StaleExternalFixtureDocumentation {}\n",
        ));
        $corpus = DocumentationSource::forProject($this->workspace)
            ->withFile('src/Documentation.php')
            ->load();
        self::assertNotFalse(file_put_contents($fixturePath, "<?php\n// changed\n", FILE_APPEND));

        $this->expectException(PhpStanConfigurationException::class);
        $this->expectExceptionMessage('changed after its documentation corpus was loaded');
        (new PhpStanExternalFixturePlanner())->plan(
            $corpus,
            PhpStanExampleConfiguration::forTokens($this->workspace, '@akashi-phpstan-example'),
        );
    }

    public function testRejectsAFileReplacedByADirectoryAfterCorpusLoading(): void
    {
        $fixturePath = $this->workspace . '/examples/fixture.php';
        self::assertNotFalse(file_put_contents(
            $fixturePath,
            "<?php\n// @akashi-phpstan-example\n\$value = 1;\n",
        ));
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/src/Documentation.php',
            "<?php\n/** @akashi-example examples/fixture.php */\nfinal class ReplacedExternalFixtureDocumentation {}\n",
        ));
        $corpus = DocumentationSource::forProject($this->workspace)
            ->withFile('src/Documentation.php')
            ->load();
        self::assertTrue(unlink($fixturePath));
        self::assertTrue(mkdir($fixturePath));

        $this->expectException(PhpStanConfigurationException::class);
        $this->expectExceptionMessage(sprintf(
            'file examples/fixture.php is unavailable under configured project %s (probed %s)',
            $this->workspace,
            $fixturePath,
        ));
        (new PhpStanExternalFixturePlanner())->plan(
            $corpus,
            PhpStanExampleConfiguration::forTokens($this->workspace, '@akashi-phpstan-example'),
        );
    }

    public function testRejectsAFileReplacedByAnExternalSymlinkAfterCorpusLoading(): void
    {
        $fixture = "<?php\n// @akashi-phpstan-example\n\$value = 1;\n";
        $fixturePath = $this->workspace . '/examples/fixture.php';
        self::assertNotFalse(file_put_contents($fixturePath, $fixture));
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/src/Documentation.php',
            "<?php\n/** @akashi-example examples/fixture.php */\nfinal class SymlinkExternalFixtureDocumentation {}\n",
        ));
        $corpus = DocumentationSource::forProject($this->workspace)
            ->withFile('src/Documentation.php')
            ->load();

        $replacement = tempnam(sys_get_temp_dir(), 'akashi-external-phpstan-target-');
        self::assertNotFalse($replacement);
        try {
            self::assertNotFalse(file_put_contents($replacement, $fixture));
            self::assertTrue(unlink($fixturePath));
            if (!@symlink($replacement, $fixturePath)) {
                self::markTestSkipped('The current platform does not permit symbolic-link fixtures.');
            }

            $this->expectException(PhpStanConfigurationException::class);
            $this->expectExceptionMessage('no longer resolves to its canonical project path');
            (new PhpStanExternalFixturePlanner())->plan(
                $corpus,
                PhpStanExampleConfiguration::forTokens($this->workspace, '@akashi-phpstan-example'),
            );
        } finally {
            if (file_exists($replacement)) {
                self::assertTrue(unlink($replacement));
            }
        }
    }

    public function testPreservesDistinctExpectationIdentitiesBeforeOrdering(): void
    {
        $lines = ['<?php', '//! 33', '//! repeated'];
        while (count($lines) < 22) {
            $lines[] = '';
        }
        $lines[] = '//! 3';
        $lines[] = '//! repeated';
        $lines[] = '$value = 1;';
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/examples/fixture.php',
            implode("\n", $lines) . "\n",
        ));
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/src/Documentation.php',
            "<?php\n/** @akashi-example examples/fixture.php */\nfinal class IdentityExternalFixtureDocumentation {}\n",
        ));

        $plan = (new PhpStanExternalFixturePlanner())->plan(
            DocumentationSource::forProject($this->workspace)
                ->withFile('src/Documentation.php')
                ->load(),
            PhpStanExampleConfiguration::forTokens($this->workspace, '//!'),
        );

        self::assertSame(
            [
                ['33', 2],
                ['repeated', 3],
                ['3', 23],
                ['repeated', 24],
            ],
            array_map(
                static fn (DiagnosticExpectation $expectation): array => [
                    $expectation->text,
                    $expectation->sourceLine,
                ],
                $plan->expectationsByFile[$this->nativePath('examples/fixture.php')],
            ),
        );
    }

    public function testOrdersExpectationsByCanonicalSourceRatherThanReferenceOrder(): void
    {
        $fixture = <<<'PHP'
            <?php

            // akashi-region: early
            //! early diagnostic
            $early = 1;
            // akashi-region-end: early

            // akashi-region: late
            //! late diagnostic
            $late = 2;
            // akashi-region-end: late
            PHP;
        $documentation = <<<'PHP'
            <?php

            /**
             * @akashi-example examples/fixture.php#late
             * @akashi-example examples/fixture.php#early
             */
            final class OrderedExternalFixtureDocumentation
            {
            }
            PHP;
        self::assertNotFalse(file_put_contents($this->workspace . '/examples/fixture.php', $fixture));
        self::assertNotFalse(file_put_contents($this->workspace . '/src/Documentation.php', $documentation));

        $plan = (new PhpStanExternalFixturePlanner())->plan(
            DocumentationSource::forProject($this->workspace)
                ->withFile('src/Documentation.php')
                ->load(),
            PhpStanExampleConfiguration::forTokens($this->workspace, '//!'),
        );

        self::assertSame(
            [
                ['early diagnostic', 4],
                ['late diagnostic', 9],
            ],
            array_map(
                static fn (DiagnosticExpectation $expectation): array => [
                    $expectation->text,
                    $expectation->sourceLine,
                ],
                $plan->expectationsByFile[$this->nativePath('examples/fixture.php')],
            ),
        );
    }

    public function testRejectsACorpusWithoutRelevantExternalExamples(): void
    {
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/README.md',
            "```php\n\$value = 1;\n```\n",
        ));

        $this->expectException(NoRelevantExamplesException::class);
        (new PhpStanExternalFixturePlanner())->plan(
            DocumentationSource::forProject($this->workspace)->withFile('README.md')->load(),
            PhpStanExampleConfiguration::forTokens($this->workspace, '@akashi-phpstan-example'),
        );
    }

    public function testPlanNormalizesOrderingWithoutDiscardingExpectations(): void
    {
        $projectRoot = new ProjectRoot($this->workspace);
        $first = new DiagnosticExpectation('first', 1);
        $second = new DiagnosticExpectation('second', 2);

        $plan = new PhpStanExternalFixturePlan(
            $projectRoot,
            ['z.php', 'a.php'],
            [
                $this->nativePath('z.php') => [$second],
                $this->nativePath('a.php') => [$first],
            ],
        );

        self::assertSame(['a.php', 'z.php'], $plan->analysisPaths);
        self::assertSame(
            [$this->nativePath('a.php'), $this->nativePath('z.php')],
            array_keys($plan->expectationsByFile),
        );
        self::assertSame([$first], $plan->expectationsByFile[$this->nativePath('a.php')]);
        self::assertSame([$second], $plan->expectationsByFile[$this->nativePath('z.php')]);
    }

    public function testPlanJoinsPathsAtAFilesystemRootWithoutDuplicatingTheSeparator(): void
    {
        $root = DIRECTORY_SEPARATOR === '\\' ? 'C:/' : '/';
        $expectationPath = str_replace('/', DIRECTORY_SEPARATOR, $root . 'fixture.php');
        $plan = new PhpStanExternalFixturePlan(
            new ProjectRoot($root),
            ['fixture.php'],
            [$expectationPath => []],
        );

        self::assertSame([$expectationPath => []], $plan->expectationsByFile);
    }

    public function testPlannerJoinsPathsAtTheCurrentFilesystemRoot(): void
    {
        $filesystemRoot = preg_match('/\A[a-zA-Z]:\//', $this->workspace) === 1
            ? substr($this->workspace, 0, 3)
            : '/';
        $workspaceRelative = ltrim(substr($this->workspace, strlen($filesystemRoot)), '/');
        $fixtureRelative = $workspaceRelative . '/examples/fixture.php';
        $documentationRelative = $workspaceRelative . '/src/Documentation.php';
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/examples/fixture.php',
            "<?php\n// @akashi-phpstan-example\n\$value = 1;\n",
        ));
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/src/Documentation.php',
            sprintf(
                "<?php\n/** @akashi-example %s */\nfinal class RootExternalFixtureDocumentation {}\n",
                $fixtureRelative,
            ),
        ));

        $plan = (new PhpStanExternalFixturePlanner())->plan(
            DocumentationSource::forProject($filesystemRoot)
                ->withFile($documentationRelative)
                ->load(),
            PhpStanExampleConfiguration::forTokens($filesystemRoot, '@akashi-phpstan-example'),
        );

        self::assertSame([$fixtureRelative], $plan->analysisPaths);
        self::assertSame([$this->nativePath('examples/fixture.php')], array_keys($plan->expectationsByFile));
    }

    /**
     * @param array<array-key, mixed> $analysisPaths
     * @param array<array-key, mixed> $expectationsByFile
     */
    #[DataProvider('invalidPlanProvider')]
    public function testPlanRejectsMalformedEvidence(
        array $analysisPaths,
        array $expectationsByFile,
        string $message,
    ): void {
        try {
            (new \ReflectionClass(PhpStanExternalFixturePlan::class))->newInstance(
                new ProjectRoot($this->workspace),
                $analysisPaths,
                $expectationsByFile,
            );
        } catch (\InvalidArgumentException $exception) {
            self::assertSame($message, $exception->getMessage());

            return;
        }

        self::fail('Malformed external PHPStan fixture plan evidence must be rejected.');
    }

    /** @return iterable<string, array{array<array-key, mixed>, array<array-key, mixed>, non-empty-string}> */
    public static function invalidPlanProvider(): iterable
    {
        yield 'empty analysis paths' => [
            [],
            [],
            'External PHPStan fixture analysis paths must form a nonempty list.',
        ];
        yield 'non-list analysis paths' => [
            ['path' => 'fixture.php'],
            [],
            'External PHPStan fixture analysis paths must form a nonempty list.',
        ];
        yield 'non-string analysis path' => [
            [1],
            [],
            'Every external PHPStan fixture analysis path must be a string.',
        ];
        yield 'non-PHP analysis path' => [
            ['fixture.md'],
            [],
            'External PHPStan fixture analysis paths must identify case-sensitive .php files.',
        ];
        yield 'duplicate normalized analysis path' => [
            ['examples/fixture.php', 'examples/./fixture.php'],
            [],
            'Duplicate external PHPStan fixture analysis path: examples/fixture.php.',
        ];
        yield 'missing expectation path' => [
            ['fixture.php'],
            [],
            'External PHPStan fixture expectations must contain exactly one entry for every analysis path.',
        ];
        yield 'unexpected expectation path' => [
            ['fixture.php'],
            ['/unexpected.php' => []],
            'External PHPStan fixture expectations must contain exactly one entry for every analysis path.',
        ];
    }

    private function nativePath(string $relativePath): string
    {
        return str_replace('/', DIRECTORY_SEPARATOR, $this->workspace . '/' . $relativePath);
    }
}
