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

use jbboehr\Akashi\Example;
use jbboehr\Akashi\Markdown\Exception\DuplicateMarkerException;
use jbboehr\Akashi\Model\ProjectPath;
use jbboehr\Akashi\Source\DocumentationSource;
use jbboehr\Akashi\Source\Exception\NoDocumentsFoundException;
use jbboehr\Akashi\Source\Exception\NoExamplesFoundException;
use jbboehr\Akashi\Source\Exception\UnsupportedSourcePathException;
use jbboehr\Akashi\Source\Exception\UnsafeSourcePathException;
use PHPUnit\Framework\TestCase;

final class DocumentationSourceTest extends TestCase
{
    private string $workspace;
    private string $projectRoot;

    protected function setUp(): void
    {
        $workspace = tempnam(sys_get_temp_dir(), 'akashi-documentation-source-');
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

            self::assertTrue(rmdir($path->getPathname()));
        }

        self::assertTrue(rmdir($this->workspace));
    }

    public function testLoadsOneOrderedCorpusFromMarkdownAndPhpDoc(): void
    {
        $this->write('README.md', "```php\necho 'readme';\n```\n");
        $this->write('docs/ignored.txt', 'Not documentation');
        $this->write('src/Example.php', <<<'PHP'
<?php
/**
 * ```php
 * echo 'phpdoc';
 * ```
 */
final class Example {}
PHP);

        $examples = iterator_to_array(
            DocumentationSource::forProject($this->projectRoot)
                ->includeDirectory('.')
                ->exclude('docs')
                ->load(),
        );

        self::assertSame(
            ['README.md PHP example 1', 'src/Example.php PHPDoc example 1'],
            array_map(static fn (Example $example): string => $example->label, $examples),
        );
        self::assertSame(
            ["echo 'readme';\n", "echo 'phpdoc';\n"],
            array_map(static fn (Example $example): string => $example->code->source, $examples),
        );
    }

    public function testIncludesArraysGeneratorsAndFileInfoIteratorsWithoutManualIteration(): void
    {
        $this->write('a.md', "```php\necho 'a';\n```\n");
        $this->write('b.php', "<?php\n/**\n * ```php\n * echo 'b';\n * ```\n */\n");
        $this->write('c.md', "```php\necho 'c';\n```\n");

        $generator = static function (): \Generator {
            yield new ProjectPath('a.md');
            yield 'b.php';
        };
        $finderLike = new \ArrayIterator([
            new \SplFileInfo($this->projectRoot . '/c.md'),
        ]);

        $corpus = DocumentationSource::forProject($this->projectRoot)
            ->includeFiles($generator())
            ->includeFiles($finderLike)
            ->load();

        self::assertSame(
            ["echo 'a';\n", "echo 'b';\n", "echo 'c';\n"],
            array_map(
                static fn (Example $example): string => $example->code->source,
                iterator_to_array($corpus),
            ),
        );
    }

    public function testRejectsUnsupportedExplicitFilesButIgnoresThemInDirectories(): void
    {
        $this->write('docs/example.md', "```php\necho 'included';\n```\n");
        $this->write('docs/example.txt', 'ignored');

        self::assertCount(
            1,
            DocumentationSource::forProject($this->projectRoot)->includeDirectory('docs')->load(),
        );

        $this->expectException(UnsupportedSourcePathException::class);
        $this->expectExceptionMessage(
            'Configured documentation file must use the case-sensitive .md or .php extension: docs/example.txt.',
        );

        DocumentationSource::forProject($this->projectRoot)->includeFile('docs/example.txt');
    }

    public function testRejectsFileInfoOutsideTheProjectRoot(): void
    {
        self::assertNotFalse(file_put_contents($this->workspace . '/outside.md', "```php\n\n```\n"));

        $this->expectException(UnsafeSourcePathException::class);
        $this->expectExceptionMessage('Included documentation file is outside the project root:');

        DocumentationSource::forProject($this->projectRoot)
            ->includeFiles([new \SplFileInfo($this->workspace . '/outside.md')]);
    }

    public function testCanonicalizesAbsoluteFileInfoPathsBeforeMakingThemProjectRelative(): void
    {
        $this->write('docs/example.md', "```php\necho 'canonical';\n```\n");

        $file = new \SplFileInfo($this->projectRoot . '/../project/docs/example.md');
        $corpus = DocumentationSource::forProject($this->projectRoot)
            ->includeFiles([$file])
            ->load();

        self::assertSame(
            ["echo 'canonical';\n"],
            array_map(
                static fn (Example $example): string => $example->code->source,
                iterator_to_array($corpus),
            ),
        );
    }

    public function testRejectsDuplicateMarkersAcrossMarkdownAndPhpDoc(): void
    {
        $this->write(
            'a.md',
            "<!-- akashi-example: duplicate -->\n```php\necho 'a';\n```\n",
        );
        $this->write('b.php', <<<'PHP'
<?php
/**
 * <!-- akashi-example: duplicate -->
 * ```php
 * echo 'b';
 * ```
 */
PHP);

        $this->expectException(DuplicateMarkerException::class);
        $this->expectExceptionMessage(
            'Duplicate marker ID duplicate at b.php:3; first declared at a.md:1.',
        );

        DocumentationSource::forProject($this->projectRoot)
            ->includeFiles(['a.md', 'b.php'])
            ->withMarkerName('akashi-example')
            ->load();
    }

    public function testRejectsASelectedManifestWithoutExamples(): void
    {
        $this->write('README.md', '# No examples');
        $this->write('src/Empty.php', "<?php\n/** No fenced examples. */\n");

        $this->expectException(NoExamplesFoundException::class);
        $this->expectExceptionMessage(
            'Configured documentation files did not contain any PHP fenced blocks.',
        );

        DocumentationSource::forProject($this->projectRoot)->includeDirectory('.')->load();
    }

    public function testRejectsASelectedManifestWithoutSupportedDocuments(): void
    {
        $this->write('docs/example.txt', 'Not a supported documentation file.');

        $this->expectException(NoDocumentsFoundException::class);
        $this->expectExceptionMessage(
            'Configured source paths did not contain any included documentation files.',
        );

        DocumentationSource::forProject($this->projectRoot)->includeDirectory('docs')->load();
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
}
