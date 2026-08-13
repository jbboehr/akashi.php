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
use jbboehr\Akashi\Synchronization\Exception\InvalidSynchronizationRegionException;
use jbboehr\Akashi\Synchronization\SynchronizationChecker;
use PHPUnit\Framework\TestCase;

final class SynchronizationRewritingTest extends TestCase
{
    private string $workspace;

    protected function setUp(): void
    {
        $workspace = tempnam(sys_get_temp_dir(), 'akashi-synchronization-rewriting-');
        self::assertNotFalse($workspace);
        self::assertTrue(unlink($workspace));
        self::assertTrue(mkdir($workspace, 0o700));

        $this->workspace = $workspace;
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

    public function testRewritesOnlySeveralMarkdownCodeSpansUsingTheirAuthoredIndentation(): void
    {
        $this->write('examples/regions.php', <<<'PHP'
<?php
// akashi-region: first
echo 'first';
// akashi-region-end: first
// akashi-region: second
if (true) {
    echo 'second';
}
// akashi-region-end: second
PHP);
        $source = <<<'MARKDOWN'
Before.

<!-- akashi-sync: examples/regions.php#first -->
  ```php title=first
  echo 'stale first';
  ```
<!-- akashi-sync-end -->

Between.

<!-- akashi-sync: examples/regions.php#second -->
  ```php
  echo 'stale second';
  ```
<!-- akashi-sync-end -->

After.
MARKDOWN;
        $expected = <<<'MARKDOWN'
Before.

<!-- akashi-sync: examples/regions.php#first -->
  ```php title=first
  echo 'first';
  ```
<!-- akashi-sync-end -->

Between.

<!-- akashi-sync: examples/regions.php#second -->
  ```php
  if (true) {
      echo 'second';
  }
  ```
<!-- akashi-sync-end -->

After.
MARKDOWN;

        $rewritten = SynchronizationChecker::forProject($this->workspace)
            ->rewrite(new Document('README.md', $source));

        self::assertSame($expected, $rewritten->contents);
    }

    public function testRewritesAPhpDocCodeSpanWithoutChangingCommentDecorationOrProse(): void
    {
        $this->write('examples/example.php', "if (true) {\n\n    echo 'current';\n}\n");
        $source = <<<'PHP'
<?php
/**
 * Existing prose.
 *
 * <!-- akashi-sync: examples/example.php -->
 *
 * ```php title=example
 * echo 'stale';
 * ```
 *
 * <!-- akashi-sync-end -->
 *
 * More prose.
 */
final class Example {}
PHP;
        $expected = <<<'PHP'
<?php
/**
 * Existing prose.
 *
 * <!-- akashi-sync: examples/example.php -->
 *
 * ```php title=example
 * if (true) {
 *
 *     echo 'current';
 * }
 * ```
 *
 * <!-- akashi-sync-end -->
 *
 * More prose.
 */
final class Example {}
PHP;

        $rewritten = SynchronizationChecker::forProject($this->workspace)
            ->rewrite(new Document('src/Example.php', $source));

        self::assertSame($expected, $rewritten->contents);
    }

    public function testPreservesCrLfWhilePopulatingAnEmptyCodeSpan(): void
    {
        $this->write('examples/nonempty.php', "echo 'current';\n");
        $source = "<!-- akashi-sync: examples/nonempty.php -->\r\n"
            . "```php\r\n```\r\n<!-- akashi-sync-end -->\r\n";
        $expected = "<!-- akashi-sync: examples/nonempty.php -->\r\n"
            . "```php\r\necho 'current';\r\n```\r\n<!-- akashi-sync-end -->\r\n";

        $rewritten = SynchronizationChecker::forProject($this->workspace)
            ->rewrite(new Document('README.md', $source));

        self::assertSame($expected, $rewritten->contents);
    }

    public function testReturnsTheRewrittenDocumentUnchangedOnASecondPass(): void
    {
        $this->write('examples/example.php', "echo 'current';\n");
        $checker = SynchronizationChecker::forProject($this->workspace);
        $first = $checker->rewrite(new Document('README.md', <<<'MARKDOWN'
<!-- akashi-sync: examples/example.php -->
```php
echo 'stale';
```
<!-- akashi-sync-end -->
MARKDOWN));

        $second = $checker->rewrite($first);

        self::assertSame($first, $second);
        self::assertSame([], $checker->check($second));
    }

    public function testRejectsCanonicalCodeThatWouldCloseItsPresentationFence(): void
    {
        $this->write('examples/example.php', <<<'PHP'
<?php
// akashi-region: unsafe
$text = <<<'MARKDOWN'
```
MARKDOWN;
// akashi-region-end: unsafe
PHP);
        $document = new Document('README.md', <<<'MARKDOWN'
<!-- akashi-sync: examples/example.php#unsafe -->
```php
echo 'stale';
```
<!-- akashi-sync-end -->
MARKDOWN);

        $this->expectException(InvalidSynchronizationRegionException::class);
        $this->expectExceptionMessage(
            'Canonical code from examples/example.php#unsafe cannot be rendered safely in the presentation at '
                . 'README.md:1.',
        );

        SynchronizationChecker::forProject($this->workspace)->rewrite($document);
    }

    public function testRejectsCanonicalCodeThatWouldCloseItsPhpDocComment(): void
    {
        $this->write('examples/example.php', <<<'PHP'
/*
*/
echo 'current';
PHP);
        $document = new Document('src/Example.php', <<<'PHP'
<?php
/**
 * <!-- akashi-sync: examples/example.php -->
 * ```php
 * echo 'stale';
 * ```
 * <!-- akashi-sync-end -->
 */
final class Example {}
PHP);

        $this->expectException(InvalidSynchronizationRegionException::class);
        $this->expectExceptionMessage(
            'Canonical code from examples/example.php cannot be rendered safely in the presentation at '
                . 'src/Example.php:3.',
        );

        SynchronizationChecker::forProject($this->workspace)->rewrite($document);
    }

    public function testAttributesCanonicalBytesThatCommonMarkCannotDecodeToTheirTarget(): void
    {
        $this->write('examples/example.php', "<?php\n\$value = \"\xFF\";\n");
        $document = new Document('README.md', <<<'MARKDOWN'
<!-- akashi-sync: examples/example.php -->
```php
echo 'stale';
```
<!-- akashi-sync-end -->
MARKDOWN);

        $this->expectException(InvalidSynchronizationRegionException::class);
        $this->expectExceptionMessage(
            'Canonical code from examples/example.php cannot be rendered safely in the presentation at README.md:1.',
        );

        SynchronizationChecker::forProject($this->workspace)->rewrite($document);
    }

    public function testAttributesAnUnsafeLaterReplacementToItsCanonicalTargetAndAuthoredDirective(): void
    {
        $this->write('examples/good.php', "echo 1;\necho 2;\necho 3;\necho 4;\necho 5;\necho 6;\n");
        $this->write('examples/bad.php', <<<'PHP'
<?php
$text = <<<'MARKDOWN'
```
MARKDOWN;
PHP);
        $document = new Document('docs/guide.md', <<<'MARKDOWN'
<!-- akashi-sync: examples/good.php -->
```php
echo 'stale';
```
<!-- akashi-sync-end -->

<!-- akashi-sync: examples/bad.php -->
```php
echo 'also stale';
```
<!-- akashi-sync-end -->
MARKDOWN);

        $this->expectException(InvalidSynchronizationRegionException::class);
        $this->expectExceptionMessage(
            'Canonical code from examples/bad.php cannot be rendered safely in the presentation at docs/guide.md:7.',
        );

        SynchronizationChecker::forProject($this->workspace)->rewrite($document);
    }

    public function testRejectsMalformedOverlappingPresentationRegionsBeforeRewriting(): void
    {
        $document = new Document('README.md', <<<'MARKDOWN'
<!-- akashi-sync: examples/one.php -->
<!-- akashi-sync: examples/two.php -->
```php
echo 'stale';
```
<!-- akashi-sync-end -->
MARKDOWN);

        $this->expectException(InvalidSynchronizationRegionException::class);
        $this->expectExceptionMessage('overlaps a nested start');

        SynchronizationChecker::forProject($this->workspace)->rewrite($document);
    }

    private function write(string $path, string $contents): void
    {
        $file = $this->workspace . '/' . $path;
        $directory = dirname($file);
        if (!is_dir($directory)) {
            self::assertTrue(mkdir($directory, 0o700, true));
        }

        self::assertNotFalse(file_put_contents($file, $contents));
    }
}
