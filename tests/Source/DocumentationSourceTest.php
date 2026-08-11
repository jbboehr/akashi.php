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
use jbboehr\Akashi\Model\Directive;
use jbboehr\Akashi\Model\ProjectPath;
use jbboehr\Akashi\Model\ReferenceLocation;
use jbboehr\Akashi\Model\ReferencedExampleSource;
use jbboehr\Akashi\Source\DocumentationSource;
use jbboehr\Akashi\Source\Exception\InvalidExampleReferenceException;
use jbboehr\Akashi\Source\Exception\NoDocumentsFoundException;
use jbboehr\Akashi\Source\Exception\NoExamplesFoundException;
use jbboehr\Akashi\Source\Exception\UnsupportedSourcePathException;
use jbboehr\Akashi\Source\Exception\UnsafeSourcePathException;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function testResolvesAndDeduplicatesCanonicalNamedExamplesWithPresentationLocations(): void
    {
        $this->write('src/Conversion.php', <<<'PHP'
<?php
/** @akashi-example examples/conversion.php#basic-conversion */
final class Conversion
{
    /**
     * @akashi-example examples/conversion.php#basic-conversion
     */
    public function convert(): void {}
}
PHP);
        $this->write('examples/conversion.php', <<<'PHP'
<?php

// akashi-region: basic-conversion
// akashi: separate-process
// akashi: expect-exception RuntimeException
throw new RuntimeException('documented');
// akashi-region-end: basic-conversion
PHP);

        $examples = iterator_to_array(
            DocumentationSource::forProject($this->projectRoot)
                ->includeFile('src/Conversion.php')
                ->load(),
        );

        self::assertCount(1, $examples);
        $example = $examples[0];
        self::assertInstanceOf(ReferencedExampleSource::class, $example->source);
        self::assertSame('examples/conversion.php#basic-conversion referenced PHP example', $example->label);
        self::assertSame('examples/conversion.php', $example->codeOrigin()->document->path->value);
        self::assertSame(4, $example->codeOrigin()->firstCodeLine);
        self::assertSame(6, $example->codeOrigin()->lastCodeLine);
        self::assertSame(
            "// akashi: separate-process\n"
                . "// akashi: expect-exception RuntimeException\n"
                . "throw new RuntimeException('documented');\n",
            $example->code->source,
        );
        self::assertTrue($example->directives->contains(Directive::SeparateProcess));
        self::assertSame('RuntimeException', $example->expectedException?->className);
        self::assertSame(4, $example->codeOrigin()->metadata->separateProcessDirectiveLine);
        self::assertSame(5, $example->codeOrigin()->metadata->expectedExceptionDirectiveLine);
        self::assertSame('basic-conversion', $example->source->region?->value);
        self::assertSame(
            ['src/Conversion.php:2', 'src/Conversion.php:6'],
            array_map(
                static fn (ReferenceLocation $reference): string => sprintf(
                    '%s:%d',
                    $reference->document->path->value,
                    $reference->line,
                ),
                $example->source->references,
            ),
        );
    }

    public function testConfiguredReferenceTagsReplaceTheDefaultAndMayBeCombined(): void
    {
        $this->write('src/Examples.php', <<<'PHP'
<?php
/**
 * @example examples/legacy.php
 * @akashi-example examples/native.php
 */
PHP);
        $this->write('examples/legacy.php', "<?php\nassert(1 === 1);\n");
        $this->write('examples/native.php', "<?php\nassert(2 === 2);\n");

        $legacy = DocumentationSource::forProject($this->projectRoot)
            ->includeFile('src/Examples.php')
            ->withPhpDocReferenceTags('example')
            ->load();
        self::assertSame(
            ['examples/legacy.php'],
            array_map(
                static fn (Example $example): string => $example->codeOrigin()->document->path->value,
                iterator_to_array($legacy),
            ),
        );

        $combined = DocumentationSource::forProject($this->projectRoot)
            ->includeFile('src/Examples.php')
            ->withPhpDocReferenceTags('akashi-example', 'example')
            ->load();
        self::assertSame(
            ['examples/legacy.php', 'examples/native.php'],
            array_map(
                static fn (Example $example): string => $example->codeOrigin()->document->path->value,
                iterator_to_array($combined),
            ),
        );
    }

    public function testWholeFileReferencesMayContainNamedRegionMarkers(): void
    {
        $this->write(
            'src/Examples.php',
            "<?php\n/** @akashi-example examples/example.php */\n",
        );
        $this->write('examples/example.php', <<<'PHP'
<?php

// akashi-region: selected
assert(true);
// akashi-region-end: selected
PHP);

        $examples = iterator_to_array(
            DocumentationSource::forProject($this->projectRoot)
                ->includeFile('src/Examples.php')
                ->load(),
        );

        self::assertCount(1, $examples);
        self::assertSame('examples/example.php', $examples[0]->codeOrigin()->document->path->value);
        self::assertSame("<?php\n\n// akashi-region: selected\nassert(true);\n// akashi-region-end: selected", $examples[0]->code->source);
        self::assertFalse($examples[0]->directives->contains(Directive::SeparateProcess));
        self::assertFalse($examples[0]->directives->contains(Directive::Skip));
    }

    public function testReportsThePresentationSiteForAMissingReferencedFile(): void
    {
        $this->write(
            'src/Examples.php',
            "<?php\n/** @akashi-example examples/missing.php */\n",
        );

        $this->expectException(InvalidExampleReferenceException::class);
        $this->expectExceptionMessage(
            'Referenced example file does not exist or is not a file: examples/missing.php. Referenced at src/Examples.php:2.',
        );

        DocumentationSource::forProject($this->projectRoot)
            ->includeFile('src/Examples.php')
            ->load();
    }

    public function testDoesNotRecursivelyDiscoverReferencesInsideCanonicalExampleFiles(): void
    {
        $this->write(
            'src/Examples.php',
            "<?php\n/** @akashi-example examples/first.php */\n",
        );
        $this->write('examples/first.php', <<<'PHP'
<?php
/** @akashi-example examples/not-selected.php */
assert(true);
PHP);

        $examples = iterator_to_array(
            DocumentationSource::forProject($this->projectRoot)
                ->includeFile('src/Examples.php')
                ->load(),
        );

        self::assertCount(1, $examples);
        self::assertSame('examples/first.php', $examples[0]->codeOrigin()->document->path->value);
    }

    #[DataProvider('invalidRegionProvider')]
    public function testRejectsInvalidNamedRegions(string $source, string $message): void
    {
        $this->write(
            'src/Examples.php',
            "<?php\n/** @akashi-example examples/example.php#selected */\n",
        );
        $this->write('examples/example.php', $source);

        $this->expectException(InvalidExampleReferenceException::class);
        $this->expectExceptionMessage($message);

        DocumentationSource::forProject($this->projectRoot)
            ->includeFile('src/Examples.php')
            ->load();
    }

    /** @return iterable<string, array{string, string}> */
    public static function invalidRegionProvider(): iterable
    {
        yield 'missing' => ["<?php\necho 1;\n", 'Referenced region selected was not found'];
        yield 'unclosed' => [
            "<?php\n// akashi-region: selected\necho 1;\n",
            'Region selected at examples/example.php:2 has no matching end marker.',
        ];
        yield 'nested' => [
            "<?php\n// akashi-region: selected\n// akashi-region: inner\necho 1;\n"
                . "// akashi-region-end: inner\n// akashi-region-end: selected\n",
            'Region inner at examples/example.php:3 is nested inside region selected from line 2.',
        ];
        yield 'orphaned end' => [
            "<?php\n// akashi-region-end: selected\n",
            'Orphaned region end selected at examples/example.php:2.',
        ];
        yield 'mismatched end' => [
            "<?php\n// akashi-region: selected\necho 1;\n// akashi-region-end: other\n",
            'Region selected at examples/example.php:2 ends with mismatched name other at line 4.',
        ];
        yield 'empty' => [
            "<?php\n// akashi-region: selected\n\n// akashi-region-end: selected\n",
            'Region selected in examples/example.php contains no PHP source.',
        ];
        yield 'duplicate' => [
            "<?php\n// akashi-region: selected\necho 1;\n// akashi-region-end: selected\n"
                . "// akashi-region: selected\necho 2;\n// akashi-region-end: selected\n",
            'Duplicate region selected in examples/example.php.',
        ];
        yield 'malformed' => [
            "<?php\n// akashi-region: INVALID\necho 1;\n",
            'Malformed region marker at examples/example.php:2.',
        ];
        yield 'not standalone' => [
            "<?php\necho 1; // akashi-region: selected\n// akashi-region-end: selected\n",
            'Region marker at examples/example.php:2 must be a standalone line comment.',
        ];
        yield 'invalid PHP' => [
            "<?php\n// akashi-region: selected\nif (\n// akashi-region-end: selected\n",
            'Unable to parse referenced example file examples/example.php:',
        ];
    }

    public function testRejectsAReferencedSymlinkThatEscapesTheProjectRoot(): void
    {
        $outside = $this->workspace . '/outside.php';
        self::assertNotFalse(file_put_contents($outside, "<?php\nassert(true);\n"));
        self::assertTrue(mkdir($this->projectRoot . '/examples', 0o700, true));
        if (!@symlink($outside, $this->projectRoot . '/examples/outside.php')) {
            self::markTestSkipped('Creating symbolic links is unavailable on this platform.');
        }
        $this->write(
            'src/Examples.php',
            "<?php\n/** @akashi-example examples/outside.php */\n",
        );

        $this->expectException(InvalidExampleReferenceException::class);
        $this->expectExceptionMessage('Referenced example file resolves outside the project root');

        DocumentationSource::forProject($this->projectRoot)
            ->includeFile('src/Examples.php')
            ->load();
    }

    public function testDeduplicatesAnInProjectSymlinkAliasByItsPhysicalFile(): void
    {
        $this->write('examples/canonical.php', "<?php\nassert(true);\n");
        if (!@symlink('canonical.php', $this->projectRoot . '/examples/alias.php')) {
            self::markTestSkipped('Creating symbolic links is unavailable on this platform.');
        }
        $this->write('src/Examples.php', <<<'PHP'
<?php
/**
 * @akashi-example examples/alias.php
 * @akashi-example examples/canonical.php
 */
PHP);

        $examples = iterator_to_array(
            DocumentationSource::forProject($this->projectRoot)
                ->includeFile('src/Examples.php')
                ->load(),
        );

        self::assertCount(1, $examples);
        self::assertSame('examples/canonical.php', $examples[0]->codeOrigin()->document->path->value);
        self::assertInstanceOf(ReferencedExampleSource::class, $examples[0]->source);
        self::assertCount(2, $examples[0]->source->references);
    }

    public function testUsesTheLexicallyFirstHardLinkAliasAsTheCanonicalPath(): void
    {
        $this->write('examples/z-canonical.php', "<?php\nassert(true);\n");
        if (!@link(
            $this->projectRoot . '/examples/z-canonical.php',
            $this->projectRoot . '/examples/a-alias.php',
        )) {
            self::markTestSkipped('Creating hard links is unavailable on this platform.');
        }
        $this->write('src/Examples.php', <<<'PHP'
<?php
/**
 * @akashi-example examples/z-canonical.php
 * @akashi-example examples/a-alias.php
 */
PHP);

        $examples = iterator_to_array(
            DocumentationSource::forProject($this->projectRoot)
                ->includeFile('src/Examples.php')
                ->load(),
        );

        self::assertCount(1, $examples);
        self::assertSame('examples/a-alias.php', $examples[0]->codeOrigin()->document->path->value);
        self::assertSame(
            'example-' . substr(sha1('external:examples/a-alias.php'), 0, 16),
            $examples[0]->id->value,
        );
        self::assertInstanceOf(ReferencedExampleSource::class, $examples[0]->source);
        self::assertCount(2, $examples[0]->source->references);
    }

    #[DataProvider('invalidReferenceTargetProvider')]
    public function testRejectsMalformedReferenceTargets(string $tag, string $message): void
    {
        $this->write('src/Examples.php', "<?php\n/** {$tag} */\n");

        $this->expectException(InvalidExampleReferenceException::class);
        $this->expectExceptionMessage($message);

        DocumentationSource::forProject($this->projectRoot)
            ->includeFile('src/Examples.php')
            ->load();
    }

    /** @return iterable<string, array{string, string}> */
    public static function invalidReferenceTargetProvider(): iterable
    {
        yield 'missing target' => [
            '@akashi-example',
            'Reference target must contain exactly one path and optional region.',
        ];
        yield 'colon typo' => [
            '@akashi-example: examples/example.php',
            'Reference tag must be followed by exactly one path and optional region.',
        ];
        yield 'trailing description' => [
            '@akashi-example examples/example.php description',
            'Reference target must contain exactly one path and optional region.',
        ];
        yield 'wrong extension' => [
            '@akashi-example examples/example.inc',
            'Referenced example path must use the case-sensitive .php extension.',
        ];
        yield 'invalid region' => [
            '@akashi-example examples/example.php#Invalid',
            'Region name must be lowercase kebab-case.',
        ];
    }

    public function testRejectsDuplicateConfiguredReferenceTagNamesImmediately(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate PHPDoc reference tag akashi-example.');

        DocumentationSource::forProject($this->projectRoot)
            ->withPhpDocReferenceTags('akashi-example', 'akashi-example');
    }

    public function testRejectsASelectedManifestWithoutExamples(): void
    {
        $this->write('README.md', '# No examples');
        $this->write('src/Empty.php', "<?php\n/** No fenced examples. */\n");

        $this->expectException(NoExamplesFoundException::class);
        $this->expectExceptionMessage(
            'Configured documentation files did not contain any PHP fenced blocks or external example references.',
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
