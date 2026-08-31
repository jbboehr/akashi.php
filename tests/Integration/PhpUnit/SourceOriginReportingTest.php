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

use jbboehr\Akashi\Example;
use jbboehr\Akashi\Integration\PhpUnit\PhpUnitRuntime;
use jbboehr\Akashi\Source\DocumentationSource;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

final class SourceOriginReportingTest extends TestCase
{
    private string $workspace;
    private string $projectRoot;

    protected function setUp(): void
    {
        $workspace = tempnam(sys_get_temp_dir(), 'akashi-source-origin-');
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

    public function testReportsMaintainedLocationsForEverySupportedExampleSource(): void
    {
        $this->write('README.md', <<<'MARKDOWN'
# Examples

```php
$ready = true;
throw new RuntimeException('markdown-inline');
```
MARKDOWN);
        $this->write('src/Examples.php', <<<'PHP'
<?php
/**
 * ```php
 * $ready = true;
 * throw new RuntimeException('phpdoc-inline');
 * ```
 *
 * @akashi-example examples/whole.php
 * @akashi-example examples/regions.php#selected
 */
PHP);
        $this->write('examples/whole.php', <<<'PHP'
<?php
throw new RuntimeException('external-whole-file');
PHP);
        $this->write('examples/regions.php', <<<'PHP'
<?php
// akashi-region: selected
$ready = true;
throw new RuntimeException('external-named-region');
// akashi-region-end: selected
PHP);

        $expectations = [
            'markdown-inline' => 'README.md:5',
            'phpdoc-inline' => 'src/Examples.php:5',
            'external-whole-file' => 'examples/whole.php:2',
            'external-named-region' => 'examples/regions.php:4',
        ];
        $examples = DocumentationSource::forProject($this->projectRoot)
            ->withFiles(['README.md', 'src/Examples.php'])
            ->load();

        self::assertCount(count($expectations), $examples);
        foreach ($examples as $example) {
            $message = self::thrownMessage($example);
            self::assertArrayHasKey($message, $expectations);

            try {
                PhpUnitRuntime::assertExample($example);
            } catch (ExpectationFailedException $failure) {
                self::assertStringContainsString('Location: ' . $expectations[$message], $failure->getMessage());
                self::assertStringNotContainsString(
                    'example start; exact failing line unavailable',
                    $failure->getMessage(),
                );
                self::assertStringContainsString($message, $failure->getMessage());

                continue;
            }

            self::fail(sprintf('Documentation example %s must fail at its maintained source.', $example->id->value));
        }
    }

    private static function thrownMessage(Example $example): string
    {
        $matched = preg_match("/RuntimeException\\('([^']+)'\\)/", $example->code->source, $matches);
        self::assertSame(1, $matched);

        return $matches[1];
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
