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

namespace jbboehr\Akashi\Tests\Formatting;

use jbboehr\Akashi\Document;
use jbboehr\Akashi\ExampleCorpus;
use jbboehr\Akashi\Formatting\Exception\FormattingExecutionException;
use jbboehr\Akashi\Formatting\Exception\FormattingOutputException;
use jbboehr\Akashi\Formatting\Exception\UnsupportedFormattingExampleException;
use jbboehr\Akashi\Formatting\FormattingChecker;
use jbboehr\Akashi\Formatting\PhpCsFixerConfiguration;
use jbboehr\Akashi\Markdown\CommonMarkExampleExtractor;
use jbboehr\Akashi\Source\DocumentationSource;
use PHPUnit\Framework\TestCase;

final class FormattingCheckerTest extends TestCase
{
    private string $workspace;

    protected function setUp(): void
    {
        $workspace = tempnam(sys_get_temp_dir(), 'akashi-format-checker-');
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
            if ($path->isFile() || $path->isLink()) {
                self::assertTrue(unlink($path->getPathname()));
            } else {
                self::assertTrue(rmdir($path->getPathname()));
            }
        }
        self::assertTrue(rmdir($this->workspace));
    }

    public function testReturnsNoMismatchForCurrentInlineCode(): void
    {
        $checker = $this->checker(<<<'PHP'
<?php
$path = $argv[count($argv) - 1];
exit(is_file($path) ? 0 : 1);
PHP);

        self::assertSame([], $checker->check($this->corpus("```php\n\$value = 1;\n```\n")));
    }

    public function testAvoidsOptionsUnavailableFromTheLowestSupportedPhpCsFixer(): void
    {
        $checker = $this->checker(<<<'PHP'
<?php
$unsupported = array_intersect($argv, ['--sequential']);
exit($unsupported === [] ? 0 : 64);
PHP);

        self::assertSame([], $checker->check($this->corpus("```php\n\$value = 1;\n```\n")));
    }

    public function testReturnsFormattedCodeWithItsMaintainedExample(): void
    {
        $checker = $this->checker(<<<'PHP'
<?php
$path = $argv[count($argv) - 1];
$source = file_get_contents($path);
file_put_contents($path, str_replace('$value=1;', '$value = 1;', $source));
PHP);

        $mismatches = $checker->check($this->corpus("```php\n\$value=1;\n```\n"));

        self::assertCount(1, $mismatches);
        self::assertSame('docs/example.md', $mismatches[0]->example->codeOrigin()->document->path->value);
        self::assertSame(2, $mismatches[0]->example->codeOrigin()->firstCodeLine);
        self::assertSame("\$value = 1;\n", $mismatches[0]->formattedCode->source);
    }

    public function testPreservesAnAuthoredOpeningTagAndIgnoresFileLevelFormatterContent(): void
    {
        $checker = $this->checker(<<<'PHP'
<?php
$path = $argv[count($argv) - 1];
$source = file_get_contents($path);
$source = preg_replace('/\A<\?php\n/', "<?php\n/** project header */\n", $source);
$source = str_replace('$value=1;', '$value = 1;', $source);
file_put_contents($path, $source);
PHP);

        $mismatches = $checker->check($this->corpus("```php\n<?php\n\$value=1;\n```\n"));

        self::assertCount(1, $mismatches);
        self::assertSame("<?php\n\$value = 1;\n", $mismatches[0]->formattedCode->source);
        self::assertStringNotContainsString('project header', $mismatches[0]->formattedCode->source);
    }

    public function testPreservesAnUppercaseAuthoredOpeningTag(): void
    {
        $checker = $this->checker(<<<'PHP'
<?php
$path = $argv[count($argv) - 1];
$source = file_get_contents($path);
file_put_contents($path, str_replace('$value=1;', '$value = 1;', $source));
PHP);

        $mismatches = $checker->check($this->corpus("```php\n<?PHP\n\$value=1;\n```\n"));

        self::assertCount(1, $mismatches);
        self::assertSame("<?PHP\n\$value = 1;\n", $mismatches[0]->formattedCode->source);
    }

    public function testPreservesFinalNewlineDifferences(): void
    {
        $example = (new CommonMarkExampleExtractor())->extract(
            new Document('docs/example.md', "```php\n\$value = 1;\n```\n"),
        )[0];
        self::assertSame("\$value = 1;\n", $example->code->source);

        $checker = $this->checker(<<<'PHP'
<?php
$path = $argv[count($argv) - 1];
$source = file_get_contents($path);
file_put_contents($path, rtrim($source, "\r\n"));
PHP);

        $mismatches = $checker->check(new ExampleCorpus($example));

        self::assertCount(1, $mismatches);
        self::assertSame('$value = 1;', $mismatches[0]->formattedCode->source);
    }

    public function testPreservesAuthoredCrlfWhenTheFormatterLeavesItCurrent(): void
    {
        $checker = $this->checker(<<<'PHP'
<?php
$path = $argv[count($argv) - 1];
exit(is_file($path) ? 0 : 1);
PHP);

        self::assertSame([], $checker->check($this->corpus("```php\r\n\$value = 1;\r\n```\r\n")));
    }

    public function testSkipsReferencedExternalExamples(): void
    {
        self::assertTrue(mkdir($this->workspace . '/src'));
        self::assertTrue(mkdir($this->workspace . '/examples'));
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/src/Example.php',
            "<?php\n/** @akashi-example examples/example.php */\n",
        ));
        self::assertNotFalse(file_put_contents($this->workspace . '/examples/example.php', "<?php\necho 1;\n"));
        $checker = $this->checker("<?php\nexit(99);\n");
        $corpus = DocumentationSource::forProject($this->workspace)->includeFile('src/Example.php')->load();

        self::assertSame([], $checker->check($corpus));
    }

    public function testContinuesCheckingInlineExamplesAfterAReferencedExample(): void
    {
        self::assertTrue(mkdir($this->workspace . '/src'));
        self::assertTrue(mkdir($this->workspace . '/examples'));
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/src/Example.php',
            "<?php\n/** @akashi-example examples/example.php */\n",
        ));
        self::assertNotFalse(file_put_contents($this->workspace . '/examples/example.php', "<?php\necho 1;\n"));
        $referenced = iterator_to_array(
            DocumentationSource::forProject($this->workspace)->includeFile('src/Example.php')->load(),
        )[0];
        $inline = (new CommonMarkExampleExtractor())->extract(
            new Document('zz/example.md', "```php\n\$value=1;\n```\n"),
        )[0];
        $checker = $this->checker(<<<'PHP'
<?php
$path = $argv[count($argv) - 1];
$source = file_get_contents($path);
file_put_contents($path, str_replace('$value=1;', '$value = 1;', $source));
PHP);

        $mismatches = $checker->check(new ExampleCorpus($referenced, $inline));

        self::assertCount(1, $mismatches);
        self::assertSame("\$value = 1;\n", $mismatches[0]->formattedCode->source);
    }

    public function testChecksInlinePhpDocExamplesAgainstTheirMaintainedLocation(): void
    {
        self::assertTrue(mkdir($this->workspace . '/src'));
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/src/Example.php',
            "<?php\n/**\n * ```php\n * \$value=1;\n * ```\n */\nfinal class Example {}\n",
        ));
        $checker = $this->checker(<<<'PHP'
<?php
$path = $argv[count($argv) - 1];
$source = file_get_contents($path);
file_put_contents($path, str_replace('$value=1;', '$value = 1;', $source));
PHP);
        $corpus = DocumentationSource::forProject($this->workspace)->includeFile('src/Example.php')->load();

        $mismatches = $checker->check($corpus);

        self::assertCount(1, $mismatches);
        self::assertSame('src/Example.php', $mismatches[0]->example->codeOrigin()->document->path->value);
        self::assertSame(4, $mismatches[0]->example->codeOrigin()->firstCodeLine);
        self::assertSame("\$value = 1;\n", $mismatches[0]->formattedCode->source);
    }

    public function testRemovesThePrivateInputAfterSuccessfulFormatting(): void
    {
        $checker = $this->checker(<<<'PHP'
<?php
$path = $argv[count($argv) - 1];
file_put_contents(dirname(__DIR__, 2) . '/captured-path', $path);
PHP);

        self::assertSame([], $checker->check($this->corpus("```php\necho 1;\n```\n")));
        $temporaryPath = file_get_contents($this->workspace . '/captured-path');
        self::assertNotFalse($temporaryPath);
        self::assertFileDoesNotExist($temporaryPath);
        self::assertDirectoryDoesNotExist(dirname($temporaryPath));
        self::assertTrue(unlink($this->workspace . '/captured-path'));
    }

    public function testRejectsCodeThatCannotBeSafelyEnclosed(): void
    {
        $checker = $this->checker("<?php\nexit(0);\n");
        $this->expectException(UnsupportedFormattingExampleException::class);
        $this->expectExceptionMessage('closing tags, additional PHP segments, inline HTML, short echo tags');

        $checker->check($this->corpus("```php\n<?php echo 1; ?>outside\n```\n"));
    }

    public function testRejectsATaglessExampleBeginningWithAShortEchoTag(): void
    {
        $checker = $this->checker("<?php\nexit(0);\n");
        $this->expectException(UnsupportedFormattingExampleException::class);
        $this->expectExceptionMessage('short echo tags');

        $checker->check($this->corpus("```php\n<?= \$value\n```\n"));
    }

    public function testDoesNotTreatAShortEchoSequenceInsideAStringAsATag(): void
    {
        $checker = $this->checker("<?php\nexit(0);\n");

        self::assertSame([], $checker->check($this->corpus("```php\n\$value = '<?=';\n```\n")));
    }

    public function testRejectsFormatterOutputWithoutTheBodyBoundary(): void
    {
        $checker = $this->checker(<<<'PHP'
<?php
$path = $argv[count($argv) - 1];
$source = file_get_contents($path);
file_put_contents($path, str_replace('__AKASHI_FORMAT_BODY_', '__BROKEN_', $source));
PHP);
        $this->expectException(FormattingOutputException::class);
        $this->expectExceptionMessage('did not preserve the body boundary');

        $checker->check($this->corpus("```php\necho 1;\n```\n"));
    }

    public function testReportsFormatterFailuresAgainstTheMaintainedSource(): void
    {
        $checker = $this->checker(<<<'PHP'
<?php
$path = $argv[count($argv) - 1];
fwrite(STDOUT, "first: {$path}\n");
fwrite(STDERR, 'second: ' . str_replace('/', '\\', $path) . "\n");
exit(8);
PHP);

        try {
            $checker->check($this->corpus("```php\necho 1;\n```\n"));
            self::fail('Expected formatter execution to fail.');
        } catch (FormattingExecutionException $exception) {
            self::assertStringContainsString('docs/example.md:2 with status 8', $exception->getMessage());
            self::assertStringEndsWith(
                "status 8.\nfirst: docs/example.md:2\n\nsecond: docs/example.md:2",
                $exception->getMessage(),
            );
            self::assertStringNotContainsString('akashi-format-', $exception->getMessage());
        }
    }

    public function testIntegratesWithTheInstalledPhpCsFixerAndProjectHeaderRule(): void
    {
        $root = dirname(__DIR__, 2);
        $checker = new FormattingChecker(PhpCsFixerConfiguration::forProject(
            $root,
            'vendor/bin/php-cs-fixer',
            '.php-cs-fixer.dist.php',
        ));

        $mismatches = $checker->check($this->corpus("```php\n\$value=1;\n```\n"));

        self::assertCount(1, $mismatches);
        self::assertSame("\$value = 1;\n", $mismatches[0]->formattedCode->source);
    }

    private function checker(string $script): FormattingChecker
    {
        self::assertTrue(mkdir($this->workspace . '/vendor/bin', 0o700, true));
        self::assertNotFalse(file_put_contents($this->workspace . '/vendor/bin/php-cs-fixer', $script));

        return new FormattingChecker(PhpCsFixerConfiguration::forProject($this->workspace));
    }

    private function corpus(string $markdown): ExampleCorpus
    {
        $examples = (new CommonMarkExampleExtractor())->extract(new Document('docs/example.md', $markdown));

        return new ExampleCorpus(...$examples);
    }
}
