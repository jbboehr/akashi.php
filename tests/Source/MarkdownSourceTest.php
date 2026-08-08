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

namespace jbboehr\Akashi\Tests\Source;

use jbboehr\Akashi\Document;
use jbboehr\Akashi\Example;
use jbboehr\Akashi\Markdown\Exception\DirectiveException;
use jbboehr\Akashi\Markdown\Exception\DuplicateMarkerException;
use jbboehr\Akashi\Markdown\Exception\InvalidMarkerMetadataException;
use jbboehr\Akashi\Markdown\Exception\NonPhpMarkerException;
use jbboehr\Akashi\Markdown\Exception\OrphanedMarkerException;
use jbboehr\Akashi\Model\Directive;
use jbboehr\Akashi\Model\InvalidMarkerException;
use jbboehr\Akashi\Model\MarkerName;
use jbboehr\Akashi\Model\ProjectRoot;
use jbboehr\Akashi\Model\ProjectPath;
use jbboehr\Akashi\Source\Exception\DuplicateDocumentException;
use jbboehr\Akashi\Source\Exception\NoDocumentsFoundException;
use jbboehr\Akashi\Source\Exception\NoExamplesFoundException;
use jbboehr\Akashi\Source\Exception\ProjectRootNotFoundException;
use jbboehr\Akashi\Source\Exception\SourcePathNotFoundException;
use jbboehr\Akashi\Source\Exception\SourceReadException;
use jbboehr\Akashi\Source\Exception\SourceException;
use jbboehr\Akashi\Source\Exception\UnsupportedSourcePathException;
use jbboehr\Akashi\Source\Exception\UnsafeSourcePathException;
use jbboehr\Akashi\Source\MarkdownSource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MarkdownSourceTest extends TestCase
{
    private string $workspace;
    private string $projectRoot;

    protected function setUp(): void
    {
        $workspace = tempnam(sys_get_temp_dir(), 'akashi-source-');
        self::assertNotFalse($workspace);
        self::assertTrue(unlink($workspace));
        self::assertTrue(mkdir($workspace . '/project', 0o700, true));

        $this->workspace = $workspace;
        $this->projectRoot = $workspace . '/project';
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

            self::assertTrue(chmod($path->getPathname(), 0o700));
            self::assertTrue(rmdir($path->getPathname()));
        }

        self::assertTrue(rmdir($this->workspace));
    }

    public function testReproducesTheYumemiDocumentManifest(): void
    {
        $this->write('README.md', "# Project\r\n");
        $this->write('docs/pages/README.md', "# Introduction\n");
        $this->write('docs/pages/SUMMARY.md', "# Summary\n");
        $this->write('docs/pages/reference/runtime.md', "# Runtime\n");
        $this->write('docs/pages/reference/notes.txt', 'Not Markdown');
        $this->write('docs/pages/reference/UPPER.MD', 'Wrong extension case');

        $documents = MarkdownSource::forProject($this->projectRoot)
            ->includeFile('README.md')
            ->includeDirectory('docs/pages')
            ->exclude('docs/pages/SUMMARY.md')
            ->loadDocuments();

        self::assertSame(
            ['README.md', 'docs/pages/README.md', 'docs/pages/reference/runtime.md'],
            $this->paths($documents),
        );
        self::assertSame("# Project\r\n", $documents[0]->contents);
        self::assertSame("# Introduction\n", $documents[1]->contents);
        self::assertSame("# Runtime\n", $documents[2]->contents);
    }

    public function testAcceptsTypedRootsAndPathsWithoutMutatingEarlierConfigurations(): void
    {
        $this->write('README.md', '# Project');
        $root = new ProjectRoot($this->projectRoot);
        $base = MarkdownSource::forProject($root);
        $included = $base->includeFile(new ProjectPath('README.md'));

        self::assertSame($root, $base->projectRoot);
        self::assertNotSame($base, $included);
        self::assertSame(['README.md'], $this->paths($included->loadDocuments()));

        try {
            $base->loadDocuments();
            self::fail('The source configuration was unexpectedly mutated.');
        } catch (NoDocumentsFoundException $exception) {
            self::assertSame('At least one Markdown include path is required.', $exception->getMessage());
        }
    }

    public function testSortsExplicitFilesIndependentlyOfConfigurationOrder(): void
    {
        $this->write('z.md', '# Last');
        $this->write('a.md', '# First');

        $documents = MarkdownSource::forProject($this->projectRoot)
            ->includeFile('z.md')
            ->includeFile('a.md')
            ->loadDocuments();

        self::assertSame(['a.md', 'z.md'], $this->paths($documents));
    }

    public function testCanIncludeTheWholeProjectRoot(): void
    {
        $this->write('README.md', '# Project');
        $this->write('docs/guide.md', '# Guide');

        $documents = MarkdownSource::forProject($this->projectRoot)
            ->includeDirectory('.')
            ->loadDocuments();

        self::assertSame(['README.md', 'docs/guide.md'], $this->paths($documents));
    }

    public function testExcludesAWholeDirectorySubtree(): void
    {
        $this->write('docs/keep.md', '# Keep');
        $this->write('docs/drafts/one.md', '# Draft');
        $this->write('docs/drafts/nested/two.md', '# Draft');

        $documents = MarkdownSource::forProject($this->projectRoot)
            ->includeDirectory(new ProjectPath('docs'))
            ->exclude(new ProjectPath('docs/drafts'))
            ->loadDocuments();

        self::assertSame(['docs/keep.md'], $this->paths($documents));
    }

    public function testRejectsAMissingProjectRootWhenLoading(): void
    {
        $root = $this->workspace . '/missing';
        $source = MarkdownSource::forProject($root)->includeFile('README.md');

        $this->expectException(ProjectRootNotFoundException::class);
        $this->expectExceptionMessage('Project root does not exist or is not a directory: ' . $root . '.');

        $source->loadDocuments();
    }

    public function testRejectsAnUnreadableProjectRoot(): void
    {
        self::assertTrue(chmod($this->projectRoot, 0o000));
        clearstatcache(true, $this->projectRoot);
        if (is_readable($this->projectRoot)) {
            self::markTestSkipped('The current user can read directories without permission bits.');
        }

        $this->expectException(SourceReadException::class);
        $this->expectExceptionMessage('Unable to read project root: ' . $this->projectRoot . '.');

        try {
            MarkdownSource::forProject($this->projectRoot)->includeFile('README.md')->loadDocuments();
        } finally {
            self::assertTrue(chmod($this->projectRoot, 0o700));
        }
    }

    public function testRejectsAMissingConfiguredFile(): void
    {
        $this->expectException(SourcePathNotFoundException::class);
        $this->expectExceptionMessage(
            'Configured Markdown file does not exist or is not a file: docs/missing.md.',
        );

        MarkdownSource::forProject($this->projectRoot)
            ->includeFile('docs/missing.md')
            ->loadDocuments();
    }

    #[DataProvider('unsupportedConfiguredFileProvider')]
    public function testRejectsConfiguredFilesWithoutTheCaseSensitiveMarkdownExtension(string $path): void
    {
        $this->expectException(UnsupportedSourcePathException::class);
        $this->expectExceptionMessage(
            'Configured Markdown file must use the case-sensitive .md extension: ' . $path . '.',
        );

        MarkdownSource::forProject($this->projectRoot)->includeFile($path);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unsupportedConfiguredFileProvider(): iterable
    {
        yield 'non-Markdown extension' => ['docs/notes.txt'];
        yield 'uppercase extension' => ['docs/UPPER.MD'];
        yield 'extensionless path' => ['docs/guide'];
        yield 'project root' => ['.'];
    }

    public function testRejectsAConfiguredFileThatIsADirectory(): void
    {
        self::assertTrue(mkdir($this->projectRoot . '/docs.md'));

        $this->expectException(SourcePathNotFoundException::class);
        $this->expectExceptionMessage(
            'Configured Markdown file does not exist or is not a file: docs.md.',
        );

        MarkdownSource::forProject($this->projectRoot)->includeFile('docs.md')->loadDocuments();
    }

    public function testRejectsAMissingConfiguredDirectory(): void
    {
        $this->expectException(SourcePathNotFoundException::class);
        $this->expectExceptionMessage(
            'Configured Markdown directory does not exist or is not a directory: docs.',
        );

        MarkdownSource::forProject($this->projectRoot)->includeDirectory('docs')->loadDocuments();
    }

    public function testRejectsAConfiguredDirectoryThatIsAFile(): void
    {
        $this->write('docs', 'Not a directory');

        $this->expectException(SourcePathNotFoundException::class);
        $this->expectExceptionMessage(
            'Configured Markdown directory does not exist or is not a directory: docs.',
        );

        MarkdownSource::forProject($this->projectRoot)->includeDirectory('docs')->loadDocuments();
    }

    public function testRejectsAMissingConfiguredExclusion(): void
    {
        $this->write('docs/guide.md', '# Guide');

        $this->expectException(SourcePathNotFoundException::class);
        $this->expectExceptionMessage(
            'Configured Markdown exclusion does not exist: docs/missing.md.',
        );

        MarkdownSource::forProject($this->projectRoot)
            ->includeDirectory('docs')
            ->exclude('docs/missing.md')
            ->loadDocuments();
    }

    public function testRejectsAConfigurationWithoutMarkdownDocuments(): void
    {
        $this->write('docs/notes.txt', 'Not Markdown');

        $this->expectException(NoDocumentsFoundException::class);
        $this->expectExceptionMessage(
            'Configured source paths did not contain any included Markdown documents.',
        );

        MarkdownSource::forProject($this->projectRoot)->includeDirectory('docs')->loadDocuments();
    }

    public function testCanExcludeTheWholeProjectRoot(): void
    {
        $this->write('README.md', '# Project');

        $this->expectException(NoDocumentsFoundException::class);
        $this->expectExceptionMessage(
            'Configured source paths did not contain any included Markdown documents.',
        );

        MarkdownSource::forProject($this->projectRoot)
            ->includeDirectory('.')
            ->exclude('.')
            ->loadDocuments();
    }

    public function testRejectsTheSameFileIncludedTwice(): void
    {
        $this->write('README.md', '# Project');

        $this->expectException(DuplicateDocumentException::class);
        $this->expectExceptionMessage(
            'Markdown document README.md resolves to the same physical file as README.md.',
        );

        MarkdownSource::forProject($this->projectRoot)
            ->includeFile('README.md')
            ->includeFile('README.md')
            ->loadDocuments();
    }

    public function testRejectsAFileReachedThroughOverlappingIncludes(): void
    {
        $this->write('docs/guide.md', '# Guide');

        $this->expectException(DuplicateDocumentException::class);
        $this->expectExceptionMessage(
            'Markdown document docs/guide.md resolves to the same physical file as docs/guide.md.',
        );

        MarkdownSource::forProject($this->projectRoot)
            ->includeFile('docs/guide.md')
            ->includeDirectory('docs')
            ->loadDocuments();
    }

    public function testRejectsDistinctHardLinksToTheSamePhysicalFile(): void
    {
        $this->write('docs/a.md', '# Guide');
        if (!@link($this->projectRoot . '/docs/a.md', $this->projectRoot . '/docs/b.md')) {
            self::markTestSkipped('Hard links are unavailable on this filesystem.');
        }

        $this->expectException(DuplicateDocumentException::class);
        $this->expectExceptionMessage(
            'Markdown document docs/b.md resolves to the same physical file as docs/a.md.',
        );

        MarkdownSource::forProject($this->projectRoot)->includeDirectory('docs')->loadDocuments();
    }

    public function testRejectsASymbolicLinkToAFileOutsideTheProject(): void
    {
        self::assertNotFalse(file_put_contents($this->workspace . '/outside.md', '# Outside'));
        $this->makeSymlink('../../outside.md', 'docs/outside.md');

        $this->expectException(UnsafeSourcePathException::class);
        $this->expectExceptionMessage(
            'Markdown document resolves outside the project root: docs/outside.md.',
        );

        MarkdownSource::forProject($this->projectRoot)->includeDirectory('docs')->loadDocuments();
    }

    public function testDoesNotFollowSymbolicLinkDirectoriesDuringRecursion(): void
    {
        $this->write('docs/visible.md', '# Visible');
        $this->write('linked/hidden.md', '# Hidden');
        $this->makeSymlink('../linked', 'docs/linked');

        $documents = MarkdownSource::forProject($this->projectRoot)
            ->includeDirectory('docs')
            ->loadDocuments();

        self::assertSame(['docs/visible.md'], $this->paths($documents));
    }

    public function testRejectsExplicitTraversalThroughASymbolicLinkDirectory(): void
    {
        $this->write('linked/hidden.md', '# Hidden');
        $this->makeSymlink('../linked', 'docs/linked');

        $this->expectException(UnsafeSourcePathException::class);
        $this->expectExceptionMessage(
            'Configured Markdown paths must not traverse symbolic-link directories: docs/linked.',
        );

        MarkdownSource::forProject($this->projectRoot)
            ->includeDirectory('docs/linked')
            ->loadDocuments();
    }

    public function testRejectsAnUnreadableDocument(): void
    {
        $this->write('docs/guide.md', '# Guide');
        self::assertTrue(chmod($this->projectRoot . '/docs/guide.md', 0o000));
        clearstatcache(true, $this->projectRoot . '/docs/guide.md');
        if (is_readable($this->projectRoot . '/docs/guide.md')) {
            self::markTestSkipped('The current user can read files without permission bits.');
        }

        $this->expectException(SourceReadException::class);
        $this->expectExceptionMessage('Unable to read Markdown document: docs/guide.md.');

        MarkdownSource::forProject($this->projectRoot)->includeFile('docs/guide.md')->loadDocuments();
    }

    public function testRejectsAnUnreadableConfiguredDirectory(): void
    {
        self::assertTrue(mkdir($this->projectRoot . '/docs'));
        self::assertTrue(chmod($this->projectRoot . '/docs', 0o000));
        clearstatcache(true, $this->projectRoot . '/docs');
        if (is_readable($this->projectRoot . '/docs')) {
            self::markTestSkipped('The current user can read directories without permission bits.');
        }

        $this->expectException(SourceReadException::class);
        $this->expectExceptionMessage('Unable to read Markdown directory: docs.');

        try {
            MarkdownSource::forProject($this->projectRoot)->includeDirectory('docs')->loadDocuments();
        } finally {
            self::assertTrue(chmod($this->projectRoot . '/docs', 0o700));
        }
    }

    public function testReportsAnUnreadableNestedDirectory(): void
    {
        self::assertTrue(mkdir($this->projectRoot . '/docs/private', 0o700, true));
        self::assertTrue(chmod($this->projectRoot . '/docs/private', 0o000));
        clearstatcache(true, $this->projectRoot . '/docs/private');
        if (is_readable($this->projectRoot . '/docs/private')) {
            self::markTestSkipped('The current user can read directories without permission bits.');
        }

        $this->expectException(SourceReadException::class);
        $this->expectExceptionMessage('Unable to read Markdown directory: docs.');

        try {
            MarkdownSource::forProject($this->projectRoot)->includeDirectory('docs')->loadDocuments();
        } finally {
            self::assertTrue(chmod($this->projectRoot . '/docs/private', 0o700));
        }
    }

    public function testLoadsADeterministicallyOrderedExampleCorpus(): void
    {
        $this->write('docs/z.md', "```php\necho 'z';\n```\n");
        $this->write('docs/a.md', "```php\necho 'a1';\n```\n\n```PHP extra\necho 'a2';\n```\n");

        $corpus = MarkdownSource::forProject($this->projectRoot)
            ->includeDirectory('docs')
            ->load();
        $examples = iterator_to_array($corpus);

        self::assertCount(3, $corpus);
        self::assertSame(
            ['docs/a.md PHP example 1', 'docs/a.md PHP example 2', 'docs/z.md PHP example 1'],
            array_map(static fn (Example $example): string => $example->label, $examples),
        );
        self::assertSame(
            ["echo 'a1';\n", "echo 'a2';\n", "echo 'z';\n"],
            array_map(static fn (Example $example): string => $example->code->source, $examples),
        );
    }

    public function testConfiguresMarkerParsingWithoutMutatingTheEarlierSource(): void
    {
        $this->write('docs/guide.md', <<<'MARKDOWN'
<!-- yumemi-example: selected-example -->
<!-- akashi: separate-process -->
```php
<?php
echo 'selected';
```
MARKDOWN);
        $source = MarkdownSource::forProject($this->projectRoot)->includeFile('docs/guide.md');
        $markedSource = $source->withMarkerName(new MarkerName('yumemi-example'));

        $unmarkedExamples = iterator_to_array($source->load());
        $markedExamples = iterator_to_array($markedSource->load());

        self::assertNull($unmarkedExamples[0]->explicitMarkerId);
        self::assertSame('selected-example', $markedExamples[0]->explicitMarkerId?->value);
        self::assertTrue($unmarkedExamples[0]->directives->contains(Directive::SeparateProcess));
        self::assertTrue($markedExamples[0]->directives->contains(Directive::SeparateProcess));
    }

    public function testWrapsAnInvalidAuthoredMarkerWithinTheSourceExceptionBoundary(): void
    {
        $this->write(
            'docs/invalid.md',
            "<!-- yumemi-example: Invalid_ID -->\n```php\necho 'invalid';\n```\n",
        );

        try {
            MarkdownSource::forProject($this->projectRoot)
                ->includeFile('docs/invalid.md')
                ->withMarkerName('yumemi-example')
                ->load();
            self::fail('The invalid authored marker was accepted.');
        } catch (SourceException $exception) {
            self::assertInstanceOf(InvalidMarkerMetadataException::class, $exception);
            self::assertSame(
                'Invalid yumemi-example marker at docs/invalid.md:1: Marker ID must use lowercase kebab-case.',
                $exception->getMessage(),
            );
            self::assertInstanceOf(InvalidMarkerException::class, $exception->getPrevious());
        }
    }

    /**
     * @param class-string<SourceException> $exception
     */
    #[DataProvider('metadataSourceExceptionProvider')]
    public function testMetadataFailuresShareTheSourceExceptionBoundary(string $exception): void
    {
        self::assertTrue(is_subclass_of($exception, SourceException::class));
    }

    /**
     * @return iterable<string, array{class-string<SourceException>}>
     */
    public static function metadataSourceExceptionProvider(): iterable
    {
        yield 'directive' => [DirectiveException::class];
        yield 'duplicate marker' => [DuplicateMarkerException::class];
        yield 'invalid marker' => [InvalidMarkerMetadataException::class];
        yield 'non-PHP marker' => [NonPhpMarkerException::class];
        yield 'orphaned marker' => [OrphanedMarkerException::class];
    }

    public function testRejectsDuplicateMarkerIdsAcrossDocuments(): void
    {
        $this->write(
            'docs/a.md',
            "<!-- yumemi-example: selected-example -->\n```php\necho 'a';\n```\n",
        );
        $this->write(
            'docs/b.md',
            "\n<!-- yumemi-example: selected-example -->\n```php\necho 'b';\n```\n",
        );

        $this->expectException(DuplicateMarkerException::class);
        $this->expectExceptionMessage(
            'Duplicate marker ID selected-example at docs/b.md:2; first declared at docs/a.md:1.',
        );

        MarkdownSource::forProject($this->projectRoot)
            ->includeDirectory('docs')
            ->withMarkerName('yumemi-example')
            ->load();
    }

    public function testRejectsAnEmptyPhpExampleCorpusSeparatelyFromAnEmptyDocumentManifest(): void
    {
        $this->write('docs/guide.md', "# Guide\n\n```javascript\nignored();\n```\n");

        $this->expectException(NoExamplesFoundException::class);
        $this->expectExceptionMessage('Configured Markdown documents did not contain any PHP fenced blocks.');

        MarkdownSource::forProject($this->projectRoot)->includeDirectory('docs')->load();
    }

    /**
     * @param non-empty-list<Document> $documents
     *
     * @return non-empty-list<string>
     */
    private function paths(array $documents): array
    {
        return array_map(
            static fn (Document $document): string => $document->path->value,
            $documents,
        );
    }

    private function write(string $path, string $contents): void
    {
        $file = $this->projectRoot . '/' . $path;
        $directory = dirname($file);
        if (!is_dir($directory)) {
            self::assertTrue(mkdir($directory, 0o700, true));
        }

        self::assertNotFalse(file_put_contents($file, $contents));
    }

    private function makeSymlink(string $target, string $path): void
    {
        $link = $this->projectRoot . '/' . $path;
        $directory = dirname($link);
        if (!is_dir($directory)) {
            self::assertTrue(mkdir($directory, 0o700, true));
        }

        if (!@symlink($target, $link)) {
            self::markTestSkipped('Symbolic links are unavailable on this filesystem.');
        }
    }
}
