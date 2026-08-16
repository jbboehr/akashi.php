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

namespace jbboehr\Akashi\Tests\Synchronization;

use jbboehr\Akashi\Document;
use jbboehr\Akashi\Model\ProjectRoot;
use jbboehr\Akashi\Synchronization\Exception\InvalidSynchronizationRegionException;
use jbboehr\Akashi\Synchronization\SynchronizationChecker;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SynchronizationCheckerTest extends TestCase
{
    private string $workspace;
    private string $projectRoot;

    protected function setUp(): void
    {
        $workspace = tempnam(sys_get_temp_dir(), 'akashi-synchronization-');
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

    public function testChecksAWholeFileWhileNormalizingLineEndingsAndItsFinalNewline(): void
    {
        $this->write('examples/whole.php', "<?php\r\n\$result = 42;");
        $document = new Document('README.md', <<<'MARKDOWN'
<!-- akashi-sync: examples/whole.php -->
```php
<?php
$result = 42;
```
<!-- akashi-sync-end -->
MARKDOWN);
        $checker = SynchronizationChecker::forProject($this->projectRoot);

        $regions = $checker->regions($document);

        self::assertCount(1, $regions);
        self::assertSame('README.md', $regions[0]->document->path->value);
        self::assertSame('examples/whole.php', $regions[0]->targetPath->value);
        self::assertNull($regions[0]->targetRegion);
        self::assertSame(1, $regions[0]->directiveLine);
        self::assertSame(6, $regions[0]->endDirectiveLine);
        self::assertSame(2, $regions[0]->location->openingFenceLine);
        self::assertSame(3, $regions[0]->location->firstCodeLine);
        self::assertSame(4, $regions[0]->location->lastCodeLine);
        self::assertSame("<?php\n\$result = 42;\n", $regions[0]->embeddedCode->source);
        self::assertSame([], $checker->check($document));
    }

    public function testAcceptsPrettierFormattedBlankSeparatorsAndPreservesTheirRawSpan(): void
    {
        $this->write('examples/whole.php', "<?php\nassert(true);\n");
        $document = new Document('README.md', <<<'MARKDOWN'
<!-- akashi-sync: examples/whole.php -->

```php
<?php
assert(true);
```

<!-- akashi-sync-end -->
MARKDOWN);

        $regions = SynchronizationChecker::forProject(new ProjectRoot($this->projectRoot))->regions($document);

        self::assertCount(1, $regions);
        self::assertSame(1, $regions[0]->directiveLine);
        self::assertSame(8, $regions[0]->endDirectiveLine);
        self::assertSame(3, $regions[0]->location->openingFenceLine);
        self::assertSame(
            "<!-- akashi-sync: examples/whole.php -->\n\n"
                . "```php\n<?php\nassert(true);\n```\n\n"
                . '<!-- akashi-sync-end -->',
            $document->lines->slice($regions[0]->regionSpan),
        );
    }

    public function testChecksANamedCanonicalRegionWithoutIncludingItsMarkers(): void
    {
        $this->write('examples/regions.php', <<<'PHP'
<?php
// akashi-region: selected
$result = strtoupper('akashi');
assert($result === 'AKASHI');
// akashi-region-end: selected
PHP);
        $document = new Document('docs/example.md', <<<'MARKDOWN'
<!-- akashi-sync: examples/regions.php#selected -->
```php
$result = strtoupper('akashi');
assert($result === 'AKASHI');
```
<!-- akashi-sync-end -->
MARKDOWN);
        $checker = SynchronizationChecker::forProject($this->projectRoot);

        $regions = $checker->regions($document);

        self::assertCount(1, $regions);
        self::assertSame('selected', $regions[0]->targetRegion?->value);
        self::assertSame([], $checker->check($document));
    }

    public function testChecksAPhpDocPresentationWithoutComparingItsCommentDecoration(): void
    {
        $this->write('examples/regions.php', <<<'PHP'
<?php
// akashi-region: selected
if (true) {
    echo "yes\n";
}
// akashi-region-end: selected
PHP);
        $document = new Document('src/Example.php', <<<'PHP'
<?php
/**
 * <!-- akashi-sync: examples/regions.php#selected -->
 *
 * ```php title=example
 * if (true) {
 *     echo "yes\n";
 * }
 * ```
 *
 * <!-- akashi-sync-end -->
 */
final class Example {}
PHP);
        $checker = SynchronizationChecker::forProject($this->projectRoot);

        $regions = $checker->regions($document);

        self::assertCount(1, $regions);
        self::assertSame(3, $regions[0]->directiveLine);
        self::assertSame(11, $regions[0]->endDirectiveLine);
        self::assertSame(6, $regions[0]->location->firstCodeLine);
        self::assertSame("if (true) {\n    echo \"yes\\n\";\n}\n", $regions[0]->embeddedCode->source);
        self::assertSame('php title=example', $regions[0]->fence->infoString);
        self::assertSame(
            " * if (true) {\n *     echo \"yes\\n\";\n * }\n",
            $document->lines->slice($regions[0]->location->codeSpan),
        );
        self::assertSame(
            " * ```php title=example\n * if (true) {\n *     echo \"yes\\n\";\n * }\n * ```\n",
            $document->lines->slice($regions[0]->location->fenceSpan),
        );
        self::assertSame(
            " * <!-- akashi-sync: examples/regions.php#selected -->\n"
                . " *\n"
                . " * ```php title=example\n"
                . " * if (true) {\n"
                . " *     echo \"yes\\n\";\n"
                . " * }\n"
                . " * ```\n"
                . " *\n"
                . " * <!-- akashi-sync-end -->\n",
            $document->lines->slice($regions[0]->regionSpan),
        );
        self::assertSame([], $checker->check($document));
    }

    public function testFindsSynchronizedPresentationsInEveryPhpDocComment(): void
    {
        $document = new Document('src/Example.php', <<<'PHP'
<?php
/**
 * <!-- akashi-sync: examples/one.php -->
 * ```php
 * echo 1;
 * ```
 * <!-- akashi-sync-end -->
 */
function one(): void {}

/**
 * <!-- akashi-sync: examples/two.php -->
 * ```php
 * echo 2;
 * ```
 * <!-- akashi-sync-end -->
 */
function two(): void {}
PHP);

        $regions = SynchronizationChecker::forProject($this->projectRoot)->regions($document);

        self::assertCount(2, $regions);
        self::assertSame('examples/one.php', $regions[0]->targetPath->value);
        self::assertSame('examples/two.php', $regions[1]->targetPath->value);
        self::assertSame(12, $regions[1]->directiveLine);
        self::assertSame(13, $regions[1]->location->openingFenceLine);
        self::assertSame(14, $regions[1]->location->firstCodeLine);
        self::assertSame(15, $regions[1]->location->closingFenceLine);
        self::assertSame(16, $regions[1]->endDirectiveLine);
    }

    public function testReportsATypedMismatchAndPreservesTheCanonicalOpeningTag(): void
    {
        $this->write('examples/whole.php', "<?php\n\$result = 42;\n");
        $document = new Document('README.md', <<<'MARKDOWN'
<!-- akashi-sync: examples/whole.php -->
```php
$result = 41;
```
<!-- akashi-sync-end -->
MARKDOWN);

        $mismatches = SynchronizationChecker::forProject($this->projectRoot)->check($document);

        self::assertCount(1, $mismatches);
        self::assertSame('README.md', $mismatches[0]->region->document->path->value);
        self::assertSame("\$result = 41;\n", $mismatches[0]->region->embeddedCode->source);
        self::assertSame('examples/whole.php', $mismatches[0]->canonicalOrigin->document->path->value);
        self::assertSame(1, $mismatches[0]->canonicalOrigin->firstCodeLine);
        self::assertSame("<?php\n\$result = 42;\n", $mismatches[0]->expectedCode->source);
    }

    public function testReportsAnEmptyPresentationAsAMismatch(): void
    {
        $this->write('examples/example.php', "echo 'canonical';\n");
        $document = new Document('README.md', <<<'MARKDOWN'
<!-- akashi-sync: examples/example.php -->
```php
```
<!-- akashi-sync-end -->
MARKDOWN);

        $mismatches = SynchronizationChecker::forProject($this->projectRoot)->check($document);

        self::assertCount(1, $mismatches);
        self::assertSame('', $mismatches[0]->region->embeddedCode->source);
        self::assertNull($mismatches[0]->region->location->lastCodeLine);
        self::assertSame("echo 'canonical';\n", $mismatches[0]->expectedCode->source);
    }

    public function testTreatsAdditionalBlankLinesAndLogicalIndentationAsSignificant(): void
    {
        $this->write('examples/example.php', "echo 'canonical';\n");
        $document = new Document('README.md', <<<'MARKDOWN'
<!-- akashi-sync: examples/example.php -->
```php
echo 'canonical';

```
<!-- akashi-sync-end -->

<!-- akashi-sync: examples/example.php -->
```php
    echo 'canonical';
```
<!-- akashi-sync-end -->
MARKDOWN);

        $mismatches = SynchronizationChecker::forProject($this->projectRoot)->check($document);

        self::assertCount(2, $mismatches);
        self::assertSame("echo 'canonical';\n\n", $mismatches[0]->region->embeddedCode->source);
        self::assertSame("    echo 'canonical';\n", $mismatches[1]->region->embeddedCode->source);
        self::assertSame("echo 'canonical';\n", $mismatches[0]->expectedCode->source);
        self::assertSame("echo 'canonical';\n", $mismatches[1]->expectedCode->source);
    }

    public function testContinuesPastACurrentPresentationToReportALaterMismatch(): void
    {
        $this->write('examples/one.php', "echo 1;\n");
        $this->write('examples/two.php', "echo 2;\n");
        $document = new Document('README.md', <<<'MARKDOWN'
<!-- akashi-sync: examples/one.php -->
```php
echo 1;
```
<!-- akashi-sync-end -->

<!-- akashi-sync: examples/two.php -->
```php
echo 3;
```
<!-- akashi-sync-end -->
MARKDOWN);

        $mismatches = SynchronizationChecker::forProject($this->projectRoot)->check($document);

        self::assertCount(1, $mismatches);
        self::assertSame('examples/two.php', $mismatches[0]->region->targetPath->value);
    }

    public function testAllowsSeveralPresentationsOfOneCanonicalSource(): void
    {
        $this->write('examples/example.php', "<?php\n");
        $document = new Document('README.md', <<<'MARKDOWN'
<!-- akashi-sync: examples/example.php -->
```php
<?php
```
<!-- akashi-sync-end -->

<!-- akashi-sync: examples/example.php -->
```php
<?php
```
<!-- akashi-sync-end -->
MARKDOWN);
        $checker = SynchronizationChecker::forProject($this->projectRoot);

        self::assertCount(2, $checker->regions($document));
        self::assertSame([], $checker->check($document));
    }

    public function testIgnoresSynchronizationSyntaxDisplayedInsideAnotherFence(): void
    {
        $document = new Document('README.md', <<<'MARKDOWN'
````markdown
<!-- akashi-sync: examples/example.php -->
```php
echo 'displayed';
```
<!-- akashi-sync-end -->
````
MARKDOWN);

        self::assertSame([], SynchronizationChecker::forProject($this->projectRoot)->regions($document));
    }

    public function testIgnoresUnrelatedHtmlCommentsAlongsideARegion(): void
    {
        $document = new Document('README.md', <<<'MARKDOWN'
<!-- table-of-contents -->

<!-- akashi-sync: examples/example.php -->
```php
echo 1;
```
<!-- akashi-sync-end -->

<!-- ordinary note -->
MARKDOWN);

        $regions = SynchronizationChecker::forProject($this->projectRoot)->regions($document);

        self::assertCount(1, $regions);
        self::assertSame('examples/example.php', $regions[0]->targetPath->value);
    }

    public function testIgnoresSynchronizationSyntaxOutsidePhpDocComments(): void
    {
        $document = new Document('src/Example.php', <<<'PHP'
<?php
echo '<!-- akashi-sync: examples/example.php -->';
PHP);

        self::assertSame([], SynchronizationChecker::forProject($this->projectRoot)->regions($document));
    }

    #[DataProvider('invalidRegionProvider')]
    public function testRejectsMalformedSynchronizationRegions(string $source, string $message): void
    {
        $this->expectException(InvalidSynchronizationRegionException::class);
        $this->expectExceptionMessage($message);

        SynchronizationChecker::forProject($this->projectRoot)
            ->regions(new Document('README.md', $source));
    }

    /** @return iterable<string, array{string, string}> */
    public static function invalidRegionProvider(): iterable
    {
        yield 'malformed start' => [
            "<!-- akashi-sync examples/example.php -->\n",
            'Malformed synchronization directive at README.md:1.',
        ];
        yield 'orphaned end' => [
            "<!-- akashi-sync-end -->\n",
            'Orphaned synchronization end directive at README.md:1.',
        ];
        yield 'start followed directly by end' => [
            "<!-- akashi-sync: examples/example.php -->\n<!-- akashi-sync-end -->\n",
            'must be followed by a PHP fence with only blank lines between',
        ];
        yield 'uppercase malformed start' => [
            "<!-- AKASHI-SYNC: examples/example.php -->\n",
            'Malformed synchronization directive at README.md:1.',
        ];
        yield 'missing fence' => [
            "<!-- akashi-sync: examples/example.php -->\nprose\n<!-- akashi-sync-end -->\n",
            'must be followed by a PHP fence with only blank lines between',
        ];
        yield 'wrong fence language' => [
            "<!-- akashi-sync: examples/example.php -->\n```text\nexample\n```\n<!-- akashi-sync-end -->\n",
            'is followed by a text fence, not a PHP fence',
        ];
        yield 'missing end' => [
            "<!-- akashi-sync: examples/example.php -->\n```php\necho 1;\n```\n",
            'has no following end directive separated only by blank lines',
        ];
        yield 'unclosed fence' => [
            "<!-- akashi-sync: examples/example.php -->\n```php\necho 1;\n",
            'has no following end directive separated only by blank lines',
        ];
        yield 'nested start' => [
            "<!-- akashi-sync: examples/one.php -->\n<!-- akashi-sync: examples/two.php -->\n",
            'overlaps a nested start',
        ];
        yield 'invalid target' => [
            "<!-- akashi-sync: examples/example.txt -->\n```php\necho 1;\n```\n<!-- akashi-sync-end -->\n",
            'Referenced example path must use the case-sensitive .php extension.',
        ];
    }

    public function testReportsMissingCanonicalSourceAtItsPresentation(): void
    {
        $document = new Document('README.md', <<<'MARKDOWN'
<!-- akashi-sync: examples/missing.php -->
```php
echo 'missing';
```
<!-- akashi-sync-end -->
MARKDOWN);

        $this->expectException(InvalidSynchronizationRegionException::class);
        $this->expectExceptionMessage(
            'Referenced example file does not exist or is not a file: examples/missing.php. '
                . 'Synchronized at README.md:1.',
        );

        SynchronizationChecker::forProject($this->projectRoot)->check($document);
    }

    public function testRejectsDuplicateCanonicalNamedRegions(): void
    {
        $this->write('examples/regions.php', <<<'PHP'
<?php
// akashi-region: selected
echo 1;
// akashi-region-end: selected
// akashi-region: selected
echo 2;
// akashi-region-end: selected
PHP);
        $document = new Document('README.md', <<<'MARKDOWN'
<!-- akashi-sync: examples/regions.php#selected -->
```php
echo 1;
```
<!-- akashi-sync-end -->
MARKDOWN);

        $this->expectException(InvalidSynchronizationRegionException::class);
        $this->expectExceptionMessage('Duplicate region selected in examples/regions.php. Synchronized at README.md:1.');

        SynchronizationChecker::forProject($this->projectRoot)->check($document);
    }

    public function testRejectsUnsupportedPresentationDocuments(): void
    {
        $this->expectException(InvalidSynchronizationRegionException::class);
        $this->expectExceptionMessage(
            'Synchronization documents must use the case-sensitive .md or .php extension: docs/example.txt.',
        );

        SynchronizationChecker::forProject($this->projectRoot)
            ->regions(new Document('docs/example.txt', ''));
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
