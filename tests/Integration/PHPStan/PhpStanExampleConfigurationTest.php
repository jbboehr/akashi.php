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
use jbboehr\Akashi\Integration\PHPStan\Exception\ExpectationParseException;
use jbboehr\Akashi\Integration\PHPStan\Exception\NoRelevantExamplesException;
use jbboehr\Akashi\Integration\PHPStan\Exception\PhpStanConfigurationException;
use jbboehr\Akashi\Integration\PHPStan\Exception\PhpStanException;
use jbboehr\Akashi\Integration\PHPStan\Exception\PhpStanVerificationException;
use jbboehr\Akashi\Integration\PHPStan\PhpStanExampleConfiguration;
use jbboehr\Akashi\Integration\PHPStan\PhpStanExampleSelector;
use jbboehr\Akashi\Model\Directive;
use jbboehr\Akashi\Model\DirectiveSet;
use jbboehr\Akashi\Model\ExampleCode;
use jbboehr\Akashi\Model\CorpusExampleId;
use jbboehr\Akashi\Model\FenceMetadata;
use jbboehr\Akashi\Model\Language;
use jbboehr\Akashi\Model\ProjectRoot;
use jbboehr\Akashi\Model\SourceLocation;
use jbboehr\Akashi\Model\SourceSpan;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PhpStanExampleConfigurationTest extends TestCase
{
    private string $projectRoot;

    protected function setUp(): void
    {
        $projectRoot = tempnam(sys_get_temp_dir(), 'akashi-phpstan-configuration-');
        self::assertNotFalse($projectRoot);
        self::assertTrue(unlink($projectRoot));
        self::assertTrue(mkdir($projectRoot, 0o700));

        $this->projectRoot = $projectRoot;
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->projectRoot)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->projectRoot, \FilesystemIterator::SKIP_DOTS),
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

        self::assertTrue(rmdir($this->projectRoot));
    }

    public function testIntegrationFailuresShareADomainExceptionBase(): void
    {
        $exceptions = [
            new PhpStanConfigurationException(),
            new NoRelevantExamplesException(),
            new ExpectationParseException(),
            new PhpStanVerificationException(),
        ];

        foreach ($exceptions as $exception) {
            try {
                throw $exception;
            } catch (PhpStanException $caught) {
                self::assertSame($exception, $caught);
            }
        }
    }

    public function testCanonicalizesATypedProjectRootAndUsesACustomPredicate(): void
    {
        self::assertTrue(mkdir($this->projectRoot . '/nested'));
        $seen = null;
        $configuration = PhpStanExampleConfiguration::forProject(
            new ProjectRoot($this->projectRoot . '/nested/..'),
            static function (Example $example) use (&$seen): bool {
                $seen = $example;

                return $example->codeOrigin()->document->path->value === 'docs/relevant.md';
            },
        );
        $example = $this->example('example-relevant-01', 'docs/relevant.md', 1, 'echo 1;');

        self::assertSame(str_replace('\\', '/', $this->projectRoot), $configuration->projectRoot->value);
        self::assertTrue($configuration->isRelevant($example));
        self::assertSame($example, $seen);
    }

    public function testAcceptsANonClosureCallable(): void
    {
        $predicate = new class () {
            public function __invoke(Example $example): bool
            {
                return str_contains($example->code->source, 'selected');
            }
        };
        $configuration = PhpStanExampleConfiguration::forProject($this->projectRoot, $predicate);

        self::assertTrue($configuration->isRelevant($this->example(
            'example-a-01',
            'docs/a.md',
            1,
            "echo 'selected';",
        )));
    }

    public function testMatchesAnySuppliedTokenAgainstCodeCaseSensitively(): void
    {
        $configuration = PhpStanExampleConfiguration::forTokens(
            $this->projectRoot,
            'unit_int<',
            '//!',
        );

        self::assertTrue($configuration->isRelevant($this->example(
            'example-a-01',
            'docs/a.md',
            1,
            '/** @return unit_int<\'meter\'> */',
        )));
        self::assertTrue($configuration->isRelevant($this->example(
            'example-a-02',
            'docs/a.md',
            2,
            '//! expected diagnostic',
        )));
        self::assertFalse($configuration->isRelevant($this->example(
            'example-a-03',
            'docs/a.md',
            3,
            '/** @return UNIT_INT<\'meter\'> */',
        )));
    }

    public function testDoesNotMatchTokensOutsideTheExtractedExampleCode(): void
    {
        $configuration = PhpStanExampleConfiguration::forTokens($this->projectRoot, 'prose-only-token');
        $example = $this->example(
            'example-a-01',
            'docs/prose-only-token.md',
            1,
            'echo 1;',
            'prose-only-token',
        );

        self::assertFalse($configuration->isRelevant($example));
    }

    /** @param list<string> $tokens */
    #[DataProvider('invalidTokenProvider')]
    public function testRejectsInvalidTokenConfigurations(array $tokens, string $message): void
    {
        $this->expectException(PhpStanConfigurationException::class);
        $this->expectExceptionMessage($message);

        self::invokeForTokens($this->projectRoot, $tokens);
    }

    /** @return iterable<string, array{list<string>, string}> */
    public static function invalidTokenProvider(): iterable
    {
        yield 'none' => [[], 'At least one PHPStan relevance token is required.'];
        yield 'empty' => [[''], 'PHPStan relevance tokens must not be empty.'];
        yield 'whitespace' => [[" \t"], 'PHPStan relevance tokens must not be empty.'];
        yield 'duplicate' => [['needle', 'needle'], 'Duplicate PHPStan relevance token: needle.'];
    }

    #[DataProvider('invalidProjectRootProvider')]
    public function testRejectsAnInvalidProjectRoot(string $suffix): void
    {
        $path = $this->projectRoot . '/' . $suffix;
        if ($suffix === 'file') {
            self::assertSame(1, file_put_contents($path, 'x'));
        }

        $this->expectException(PhpStanConfigurationException::class);
        $this->expectExceptionMessage(
            'PHPStan project root does not exist or is not a directory: ' . str_replace('\\', '/', $path) . '.',
        );

        PhpStanExampleConfiguration::forProject($path, static fn (Example $example): bool => true);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidProjectRootProvider(): iterable
    {
        yield 'missing' => ['missing'];
        yield 'file' => ['file'];
    }

    public function testRejectsAnUnreadableProjectRootWhenPermissionsCanExpressIt(): void
    {
        $path = $this->projectRoot . '/unreadable';
        self::assertTrue(mkdir($path, 0o700));
        self::assertTrue(chmod($path, 0o000));

        if (is_readable($path)) {
            self::assertTrue(chmod($path, 0o700));
            self::markTestSkipped('This filesystem user can read a directory without read permission bits.');
        }

        try {
            PhpStanExampleConfiguration::forProject($path, static fn (Example $example): bool => true);
        } catch (PhpStanConfigurationException $exception) {
            self::assertTrue(chmod($path, 0o700));
            self::assertSame('PHPStan project root is not readable: ' . $path . '.', $exception->getMessage());

            return;
        }

        self::assertTrue(chmod($path, 0o700));
        self::fail('An unreadable PHPStan project root must be rejected.');
    }

    public function testSelectsARelevantSubcorpusInOriginalOrder(): void
    {
        $first = $this->example('example-a-01', 'docs/a.md', 1, 'first token');
        $ignored = $this->example('example-a-02', 'docs/a.md', 2, 'ignored');
        $last = $this->example('example-b-01', 'docs/b.md', 1, 'last token');
        $configuration = PhpStanExampleConfiguration::forTokens($this->projectRoot, 'token');

        $selected = (new PhpStanExampleSelector())->select(
            new ExampleCorpus($first, $ignored, $last),
            $configuration,
        );

        self::assertCount(2, $selected);
        self::assertSame([$first, $last], iterator_to_array($selected));
    }

    public function testRejectsAnEmptyRelevantSelection(): void
    {
        $configuration = PhpStanExampleConfiguration::forTokens($this->projectRoot, 'missing-token');

        $this->expectException(NoRelevantExamplesException::class);
        $this->expectExceptionMessage(
            'No PHPStan-relevant examples were selected for project ' . $configuration->projectRoot->value . '.',
        );

        (new PhpStanExampleSelector())->select(
            new ExampleCorpus($this->example('example-a-01', 'docs/a.md', 1, 'echo 1;')),
            $configuration,
        );
    }

    public function testRuntimeSkipDoesNotExcludeAPhpStanRelevantExample(): void
    {
        $example = $this->example(
            'example-a-01',
            'docs/a.md',
            1,
            'relevant-token();',
            directives: new DirectiveSet(Directive::Skip),
        );
        $configuration = PhpStanExampleConfiguration::forTokens($this->projectRoot, 'relevant-token');

        $selected = (new PhpStanExampleSelector())->select(new ExampleCorpus($example), $configuration);

        self::assertSame([$example], iterator_to_array($selected));
    }

    /** @param positive-int $ordinal */
    private function example(
        string $id,
        string $path,
        int $ordinal,
        string $source,
        ?string $documentContents = null,
        DirectiveSet $directives = new DirectiveSet(),
    ): Example {
        $sourceLength = strlen($source);

        return Example::fromInline(
            corpusId: new CorpusExampleId($id),
            label: $path . ' PHP example ' . $ordinal,
            document: new Document($path, $documentContents ?? $source),
            location: new SourceLocation(
                1,
                2,
                2,
                3,
                new SourceSpan(0, $sourceLength),
                new SourceSpan(0, $sourceLength),
            ),
            language: new Language('php'),
            code: new ExampleCode($source),
            fence: new FenceMetadata('php', '`', 3, 0),
            ordinal: $ordinal,
            directives: $directives,
        );
    }

    /** @param list<string> $tokens */
    private static function invokeForTokens(string $projectRoot, array $tokens): mixed
    {
        return (new \ReflectionMethod(PhpStanExampleConfiguration::class, 'forTokens'))->invokeArgs(
            null,
            [$projectRoot, ...$tokens],
        );
    }
}
