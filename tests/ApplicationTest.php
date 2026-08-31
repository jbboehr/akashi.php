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

namespace jbboehr\Akashi\Tests;

use Composer\InstalledVersions;
use jbboehr\Akashi\Application;
use jbboehr\Akashi\Cli\ArgumentInput;
use jbboehr\Akashi\Cli\ExitCode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;

final class ApplicationTest extends TestCase
{
    private string $workspace;

    protected function setUp(): void
    {
        $workspace = tempnam(sys_get_temp_dir(), 'akashi-cli-');
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
                continue;
            }

            self::assertTrue(rmdir($path->getPathname()));
        }

        self::assertTrue(rmdir($this->workspace));
    }

    /**
     * @param list<string> $arguments
     */
    #[DataProvider('helpArgumentsProvider')]
    public function testDisplaysHelp(array $arguments): void
    {
        $result = $this->runApplication($arguments);

        self::assertSame(ExitCode::Success->value, $result['status']);
        self::assertStringContainsString('Usage:', $result['stdout']);
        self::assertStringEndsWith("\n", $result['stdout']);
        self::assertSame('', $result['stderr']);
    }

    /**
     * @return iterable<string, array{list<string>}>
     */
    public static function helpArgumentsProvider(): iterable
    {
        yield 'no arguments' => [[]];
        yield 'long root option' => [['--help']];
        yield 'short root option' => [['-h']];
        yield 'long command option' => [['extract', '--help']];
        yield 'short command option' => [['extract', '-h']];
        yield 'long sync option' => [['sync', '--help']];
        yield 'short sync option' => [['sync', '-h']];
        yield 'long format option' => [['format', '--help']];
        yield 'short format option' => [['format', '-h']];
    }

    /**
     * @param list<string> $arguments
     */
    #[DataProvider('versionArgumentsProvider')]
    public function testDisplaysTheInstalledPackageVersion(array $arguments): void
    {
        $result = $this->runApplication($arguments);
        $version = InstalledVersions::getPrettyVersion('jbboehr/akashi') ?? 'unknown';

        self::assertSame(ExitCode::Success->value, $result['status']);
        self::assertSame('Akashi ' . $version . PHP_EOL, $result['stdout']);
        self::assertSame('', $result['stderr']);
    }

    /**
     * @return iterable<string, array{list<string>}>
     */
    public static function versionArgumentsProvider(): iterable
    {
        yield 'long option' => [['--version']];
        yield 'short option' => [['-V']];
    }

    public function testGeneratedCommandHelpDescribesSingleValuedOptionsAccurately(): void
    {
        $result = $this->runApplication(['extract', '--help']);

        self::assertSame(ExitCode::Success->value, $result['status']);
        self::assertStringContainsString('--legacy-marker-name=LEGACY-MARKER-NAME', $result['stdout']);
        self::assertStringNotContainsString('multiple values allowed', $result['stdout']);
        self::assertSame('', $result['stderr']);
    }

    public function testRejectsSilentModeWithAVisibleUsageDiagnostic(): void
    {
        $result = $this->runApplication(['--silent']);

        self::assertSame(ExitCode::UsageError->value, $result['status']);
        self::assertSame('', $result['stdout']);
        self::assertStringContainsString('"--silent" option is not supported', $result['stderr']);
    }

    public function testRestoresShellVerbosityAfterEachInvocation(): void
    {
        $environmentHadVerbosity = array_key_exists('SHELL_VERBOSITY', $_ENV);
        $environmentVerbosity = $_ENV['SHELL_VERBOSITY'] ?? null;
        $serverHadVerbosity = array_key_exists('SHELL_VERBOSITY', $_SERVER);
        $serverVerbosity = $_SERVER['SHELL_VERBOSITY'] ?? null;
        $processVerbosity = getenv('SHELL_VERBOSITY');
        $restore = static function (array &$variables, bool $hadVerbosity, mixed $verbosity): void {
            if ($hadVerbosity) {
                $variables['SHELL_VERBOSITY'] = $verbosity;

                return;
            }

            unset($variables['SHELL_VERBOSITY']);
        };

        unset($_ENV['SHELL_VERBOSITY'], $_SERVER['SHELL_VERBOSITY']);
        if (function_exists('putenv')) {
            self::assertTrue(putenv('SHELL_VERBOSITY'));
        }

        try {
            self::assertSame(ExitCode::UsageError->value, $this->runApplication(['--silent'])['status']);
            self::assertArrayNotHasKey('SHELL_VERBOSITY', $_ENV);
            self::assertArrayNotHasKey('SHELL_VERBOSITY', $_SERVER);
            self::assertFalse(getenv('SHELL_VERBOSITY'));

            $nextRun = $this->runApplication(['list', '--raw']);
            self::assertSame(ExitCode::Success->value, $nextRun['status']);
            self::assertMatchesRegularExpression('/^extract\h/m', $nextRun['stdout']);
        } finally {
            $restore($_ENV, $environmentHadVerbosity, $environmentVerbosity);
            $restore($_SERVER, $serverHadVerbosity, $serverVerbosity);
            if (function_exists('putenv')) {
                self::assertTrue(putenv(
                    'SHELL_VERBOSITY' . ($processVerbosity === false ? '' : '=' . $processVerbosity),
                ));
            }
        }
    }

    public function testQuietModeSuppressesSuccessButPreservesFailures(): void
    {
        $file = $this->workspace . '/examples.md';
        self::assertNotFalse(file_put_contents(
            $file,
            "<!-- selected-example: chosen -->\n```php\necho 'selected';\n```\n",
        ));

        $success = $this->runApplication([
            'extract',
            '--quiet',
            '--legacy-marker-name',
            'selected-example',
            $file,
            'chosen',
        ]);
        $failure = $this->runApplication([
            'extract',
            '--quiet',
            '--legacy-marker-name=selected-example',
            $file,
            'missing',
        ]);

        self::assertSame(ExitCode::Success->value, $success['status']);
        self::assertSame('', $success['stdout']);
        self::assertSame('', $success['stderr']);
        self::assertSame(ExitCode::CommandFailure->value, $failure['status']);
        self::assertSame('', $failure['stdout']);
        self::assertStringStartsWith('Extraction failed:', $failure['stderr']);
    }

    /**
     * @param list<string> $arguments
     */
    #[DataProvider('commandAbbreviationProvider')]
    public function testRejectsCommandAbbreviations(array $arguments, string $abbreviation): void
    {
        $result = $this->runApplication($arguments);

        self::assertSame(ExitCode::UsageError->value, $result['status']);
        self::assertSame('', $result['stdout']);
        self::assertStringContainsString(sprintf('Command "%s" is not defined.', $abbreviation), $result['stderr']);
    }

    /**
     * @return iterable<string, array{list<string>, string}>
     */
    public static function commandAbbreviationProvider(): iterable
    {
        yield 'direct command' => [['ext', '--help'], 'ext'];
        yield 'help command' => [['help', 'ext'], 'ext'];
        yield 'one-letter help command' => [['help', 'f'], 'f'];
    }

    public function testPreservesMarkupInUsageDiagnostics(): void
    {
        $result = $this->runApplication(['--ansi', '<info>unknown</info>']);

        self::assertSame(ExitCode::UsageError->value, $result['status']);
        self::assertSame('', $result['stdout']);
        self::assertStringContainsString('Command "<info>unknown</info>" is not defined.', $result['stderr']);
        self::assertStringNotContainsString("\033", $result['stderr']);
    }

    public function testListsAkashiAndCompletionCommands(): void
    {
        $result = $this->runApplication(['list', '--raw']);

        self::assertSame(ExitCode::Success->value, $result['status']);
        self::assertMatchesRegularExpression('/^completion\h/m', $result['stdout']);
        self::assertMatchesRegularExpression('/^extract\h/m', $result['stdout']);
        self::assertMatchesRegularExpression('/^format\h/m', $result['stdout']);
        self::assertMatchesRegularExpression('/^sync\h/m', $result['stdout']);
        self::assertSame('', $result['stderr']);
    }

    public function testQuietCompletionFailureRetainsItsDiagnostic(): void
    {
        $result = $this->runApplication(['completion', 'invalid', '--quiet']);

        self::assertSame(ExitCode::UsageError->value, $result['status']);
        self::assertSame('', $result['stdout']);
        self::assertStringContainsString('Detected shell "invalid"', $result['stderr']);
    }

    public function testExtractsOnlyTheSelectedCodeWithTheLegacyFinalLf(): void
    {
        $file = $this->workspace . '/examples.md';
        self::assertNotFalse(file_put_contents(
            $file,
            "<!-- selected-example: chosen -->\r\n"
                . "```php\r\n"
                . "<?php\r\n\r\n"
                . "echo 'selected';\r\n"
                . "```\r\n\r\n"
                . "```php\r\n"
                . "echo 'unselected';\r\n"
                . "```\r\n",
        ));

        $result = $this->runApplication([
            'extract',
            '--legacy-marker-name=selected-example',
            $file,
            'chosen',
        ]);

        self::assertSame(ExitCode::Success->value, $result['status']);
        self::assertSame("<?php\r\n\r\necho 'selected';\n", $result['stdout']);
        self::assertSame('', $result['stderr']);
    }

    public function testExtractsCanonicalAkashiMetadataWithoutALegacyMarkerNameOption(): void
    {
        $file = $this->workspace . '/canonical.md';
        self::assertNotFalse(file_put_contents(
            $file,
            "<!-- akashi: example=chosen -->\n```php\necho 'canonical';\n```\n",
        ));

        $result = $this->runApplication(['extract', $file, 'chosen']);

        self::assertSame(ExitCode::Success->value, $result['status']);
        self::assertSame("echo 'canonical';\n", $result['stdout']);
        self::assertSame('', $result['stderr']);
    }

    public function testExtractedSourceBypassesConsoleMarkupAndAnsiFormatting(): void
    {
        $file = $this->workspace . '/markup.md';
        self::assertNotFalse(file_put_contents(
            $file,
            "<!-- selected-example: markup -->\n```php\necho '<info>literal</info>';\n```\n",
        ));

        $result = $this->runApplication([
            '--ansi',
            'extract',
            '--legacy-marker-name=selected-example',
            $file,
            'markup',
        ]);

        self::assertSame(ExitCode::Success->value, $result['status']);
        self::assertSame("echo '<info>literal</info>';\n", $result['stdout']);
        self::assertStringNotContainsString("\033", $result['stdout']);
        self::assertSame('', $result['stderr']);
    }

    public function testExtractsAMarkedPhpDocExample(): void
    {
        $file = $this->workspace . '/examples.php';
        self::assertNotFalse(file_put_contents($file, <<<'PHP'
<?php

/**
 * <!-- selected-example: chosen -->
 * ```php
 * echo 'phpdoc';
 * ```
 */
PHP));

        $result = $this->runApplication([
            'extract',
            '--legacy-marker-name=selected-example',
            $file,
            'chosen',
        ]);

        self::assertSame(ExitCode::Success->value, $result['status']);
        self::assertSame("echo 'phpdoc';\n", $result['stdout']);
        self::assertSame('', $result['stderr']);
    }

    public function testExtractsACanonicallyNamedReferencedPhpExample(): void
    {
        self::assertTrue(mkdir($this->workspace . '/src', 0o700));
        self::assertTrue(mkdir($this->workspace . '/examples', 0o700));
        $document = $this->workspace . '/src/Examples.php';
        self::assertNotFalse(file_put_contents(
            $document,
            "<?php\n/** @akashi-example examples/canonical.php */\n",
        ));
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/examples/canonical.php',
            "<?php\n// akashi: example=canonical-reference\necho 'referenced';\n",
        ));

        $result = $this->runApplication([
            'extract',
            '--project-root=' . $this->workspace,
            $document,
            'canonical-reference',
        ]);

        self::assertSame(ExitCode::Success->value, $result['status']);
        self::assertSame(
            "<?php\n// akashi: example=canonical-reference\necho 'referenced';\n",
            $result['stdout'],
        );
        self::assertSame('', $result['stderr']);
    }

    public function testExtractsAProjectRelativeDocumentWithAnExplicitProjectRoot(): void
    {
        self::assertTrue(mkdir($this->workspace . '/docs', 0o700));
        $file = $this->workspace . '/docs/examples.md';
        self::assertNotFalse(file_put_contents(
            $file,
            "<!-- selected-example: chosen -->\n```php\necho 'rooted';\n```\n",
        ));

        $result = $this->runApplication([
            'extract',
            '--legacy-marker-name=selected-example',
            '--project-root=' . $this->workspace,
            $file,
            'chosen',
        ]);

        self::assertSame(ExitCode::Success->value, $result['status']);
        self::assertSame("echo 'rooted';\n", $result['stdout']);
        self::assertSame('', $result['stderr']);
    }

    /**
     * @param list<string> $arguments
     */
    #[DataProvider('usageFailureProvider')]
    public function testReportsUsageFailuresWithoutWritingToStandardOutput(array $arguments, string $message): void
    {
        $result = $this->runApplication($arguments);

        self::assertSame(ExitCode::UsageError->value, $result['status']);
        self::assertSame('', $result['stdout']);
        self::assertStringContainsString($message, $result['stderr']);
    }

    /**
     * @return iterable<string, array{list<string>, string}>
     */
    public static function usageFailureProvider(): iterable
    {
        yield 'unknown command' => [['unknown'], 'Command "unknown" is not defined.'];
        yield 'duplicate marker name' => [
            ['extract', '--legacy-marker-name=first', '--legacy-marker-name=second', 'examples.md', 'chosen'],
            'The --legacy-marker-name option may be specified only once.',
        ];
        yield 'duplicate marker name surrounding command' => [
            ['--legacy-marker-name=first', 'extract', '--legacy-marker-name=second', 'examples.md', 'chosen'],
            'The --legacy-marker-name option may be specified only once.',
        ];
        yield 'duplicate project root' => [
            [
                'extract',
                '--legacy-marker-name=selected-example',
                '--project-root=first',
                '--project-root=second',
                'examples.md',
                'chosen',
            ],
            'The --project-root option may be specified only once.',
        ];
        yield 'unknown extract option' => [
            ['extract', '--legacy-marker-name=selected-example', '--unknown', 'examples.md', 'chosen'],
            'The "--unknown" option does not exist.',
        ];
        yield 'missing positional argument' => [
            ['extract', '--legacy-marker-name=selected-example', 'examples.md'],
            'Not enough arguments (missing: "example-id").',
        ];
        yield 'missing sync mode' => [
            ['sync', 'README.md'],
            'The sync command requires exactly one of --check or --write.',
        ];
        yield 'duplicate sync check mode' => [
            ['sync', '--check', '--check', 'README.md'],
            'The --check option may be specified only once.',
        ];
        yield 'duplicate sync write mode' => [
            ['sync', '--write', '--write', 'README.md'],
            'The --write option may be specified only once.',
        ];
        yield 'missing sync file' => [
            ['sync', '--check'],
            'Not enough arguments (missing: "files").',
        ];
        yield 'mutually exclusive sync modes' => [
            ['sync', '--check', '--write', 'README.md'],
            'The --check and --write options are mutually exclusive.',
        ];
        yield 'unknown sync option' => [
            ['sync', '--check', '--unknown', 'README.md'],
            'The "--unknown" option does not exist.',
        ];
        yield 'duplicate sync project root' => [
            ['sync', '--check', '--project-root=first', '--project-root=second', 'README.md'],
            'The --project-root option may be specified only once.',
        ];
        yield 'missing format mode' => [
            ['format', 'README.md'],
            'The format command requires exactly one of --check or --write.',
        ];
        yield 'duplicate format check mode' => [
            ['format', '--check', '--check', 'README.md'],
            'The --check option may be specified only once.',
        ];
        yield 'missing format file' => [
            ['format', '--check'],
            'Not enough arguments (missing: "files").',
        ];
        yield 'duplicate format write mode' => [
            ['format', '--write', '--write', 'README.md'],
            'The --write option may be specified only once.',
        ];
        yield 'mutually exclusive format modes' => [
            ['format', '--check', '--write', 'README.md'],
            'The --check and --write options are mutually exclusive.',
        ];
        yield 'unknown format option' => [
            ['format', '--check', '--unknown', 'README.md'],
            'The "--unknown" option does not exist.',
        ];
        yield 'duplicate formatter executable' => [
            [
                'format',
                '--check',
                '--php-cs-fixer=first',
                '--php-cs-fixer=second',
                'README.md',
            ],
            'The --php-cs-fixer option may be specified only once.',
        ];
        yield 'duplicate formatter configuration' => [
            ['format', '--check', '--config=first', '--config=second', 'README.md'],
            'The --config option may be specified only once.',
        ];
        yield 'duplicate format project root' => [
            ['format', '--check', '--project-root=first', '--project-root=second', 'README.md'],
            'The --project-root option may be specified only once.',
        ];
    }

    public function testReportsExtractionFailuresWithoutWritingToStandardOutput(): void
    {
        $file = $this->workspace . '/examples.md';
        self::assertNotFalse(file_put_contents($file, "```php\necho 1;\n```\n"));

        $result = $this->runApplication([
            'extract',
            '--legacy-marker-name=selected-example',
            $file,
            'missing',
        ]);

        self::assertSame(ExitCode::CommandFailure->value, $result['status']);
        self::assertSame('', $result['stdout']);
        self::assertStringContainsString(
            'Named example ID missing was not found in the example corpus.',
            $result['stderr'],
        );
    }

    #[DataProvider('metadataFailureProvider')]
    public function testReportsEachMetadataFailureAsAnExtractionFailure(string $markdown, string $message): void
    {
        $file = $this->workspace . '/metadata.md';
        self::assertNotFalse(file_put_contents($file, $markdown));

        $result = $this->runApplication([
            'extract',
            '--legacy-marker-name=selected-example',
            $file,
            'chosen',
        ]);

        self::assertSame(ExitCode::CommandFailure->value, $result['status']);
        self::assertSame('', $result['stdout']);
        self::assertStringContainsString($message, $result['stderr']);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function metadataFailureProvider(): iterable
    {
        yield 'directive' => [
            "<!-- akashi: unknown -->\n```php\necho 1;\n```\n",
            'Unknown property "unknown"',
        ];
        yield 'duplicate marker' => [
            "<!-- selected-example: chosen -->\n```php\necho 1;\n```\n\n"
                . "<!-- selected-example: chosen -->\n```php\necho 2;\n```\n",
            'Duplicate named example ID chosen',
        ];
        yield 'invalid marker' => [
            "<!-- selected-example: Invalid_ID -->\n```php\necho 1;\n```\n",
            'Invalid selected-example marker',
        ];
        yield 'non-PHP marker' => [
            "<!-- selected-example: chosen -->\n```javascript\necho 1;\n```\n",
            'is followed by a javascript fence, not a PHP fence',
        ];
        yield 'orphaned marker' => [
            "<!-- selected-example: chosen -->\n\nIntervening prose.\n",
            'is not followed by a fenced code block',
        ];
    }

    public function testValidatesTheNamedExampleIdBeforeReadingTheFile(): void
    {
        $result = $this->runApplication([
            'extract',
            '--legacy-marker-name=selected-example',
            $this->workspace . '/missing.md',
            'Invalid ID',
        ]);

        self::assertSame(ExitCode::CommandFailure->value, $result['status']);
        self::assertSame('', $result['stdout']);
        self::assertStringContainsString('Named example ID must use lowercase kebab-case.', $result['stderr']);
        self::assertStringNotContainsString('does not exist', $result['stderr']);
    }

    public function testRejectsAWhitespaceOnlyFilePath(): void
    {
        $result = $this->runApplication([
            'extract',
            '--legacy-marker-name=selected-example',
            '  ',
            'chosen',
        ]);

        self::assertSame(ExitCode::CommandFailure->value, $result['status']);
        self::assertSame('', $result['stdout']);
        self::assertStringContainsString('Documentation file path must not be empty.', $result['stderr']);
    }

    public function testAcceptsAProjectRelativeFilePath(): void
    {
        $file = $this->workspace . '/relative.md';
        self::assertNotFalse(file_put_contents(
            $file,
            "<!-- selected-example: chosen -->\n```php\necho 'relative';\n```\n",
        ));
        $previousDirectory = getcwd();
        self::assertNotFalse($previousDirectory);
        self::assertTrue(chdir($this->workspace));

        try {
            $result = $this->runApplication([
                'extract',
                '--legacy-marker-name=selected-example',
                'relative.md',
                'chosen',
            ]);
        } finally {
            self::assertTrue(chdir($previousDirectory));
        }

        self::assertSame(ExitCode::Success->value, $result['status']);
        self::assertSame("echo 'relative';\n", $result['stdout']);
        self::assertSame('', $result['stderr']);
    }

    public function testNormalizesBackslashesInTheFilePath(): void
    {
        $file = $this->workspace . '/backslashes.md';
        self::assertNotFalse(file_put_contents(
            $file,
            "<!-- selected-example: chosen -->\n```php\necho 'backslashes';\n```\n",
        ));

        $result = $this->runApplication([
            'extract',
            '--legacy-marker-name=selected-example',
            str_replace('/', '\\', $file),
            'chosen',
        ]);

        self::assertSame(ExitCode::Success->value, $result['status']);
        self::assertSame("echo 'backslashes';\n", $result['stdout']);
        self::assertSame('', $result['stderr']);
    }

    public function testAppendsAnLfToAnUnclosedFenceWithoutDroppingCode(): void
    {
        $file = $this->workspace . '/unclosed.md';
        self::assertNotFalse(file_put_contents(
            $file,
            "<!-- selected-example: chosen -->\n```php\necho 'unclosed';",
        ));

        $result = $this->runApplication([
            'extract',
            '--legacy-marker-name=selected-example',
            $file,
            'chosen',
        ]);

        self::assertSame(ExitCode::Success->value, $result['status']);
        self::assertSame("echo 'unclosed';\n", $result['stdout']);
        self::assertSame('', $result['stderr']);
    }

    public function testChecksSeveralCurrentSynchronizedDocumentsWithoutOutput(): void
    {
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/canonical.php',
            "<?php\n\necho 'current';\n",
        ));
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/example.md',
            "<!-- akashi-sync: canonical.php -->\n"
                . "```php\n<?php\n\necho 'current';\n```\n"
                . "<!-- akashi-sync-end -->\n",
        ));
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/Example.php',
            <<<'PHP'
<?php

/**
 * <!-- akashi-sync: canonical.php -->
 * ```php
 * <?php
 *
 * echo 'current';
 * ```
 * <!-- akashi-sync-end -->
 */
PHP,
        ));

        $result = $this->runApplication([
            'sync',
            '--check',
            '--project-root=' . $this->workspace,
            $this->workspace . '/example.md',
            $this->workspace . '/Example.php',
        ]);

        self::assertSame(ExitCode::Success->value, $result['status']);
        self::assertSame('', $result['stdout']);
        self::assertSame('', $result['stderr']);
    }

    public function testWritesSeveralSynchronizedDocumentsInDeterministicPathOrder(): void
    {
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/canonical.php',
            "<?php\n\necho 'current';\n",
        ));
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/first.md',
            "<!-- akashi-sync: canonical.php -->\n"
                . "```php\n<?php\n\necho 'stale';\n```\n"
                . "<!-- akashi-sync-end -->\n",
        ));
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/Second.php',
            <<<'PHP'
<?php

/**
 * <!-- akashi-sync: canonical.php -->
 * ```php
 * <?php
 *
 * echo 'also stale';
 * ```
 * <!-- akashi-sync-end -->
 */
PHP,
        ));

        $result = $this->runApplication([
            'sync',
            '--project-root=' . $this->workspace,
            $this->workspace . '/Second.php',
            '--write',
            $this->workspace . '/first.md',
        ]);

        self::assertSame(ExitCode::Success->value, $result['status']);
        self::assertSame('', $result['stdout']);
        self::assertSame("Updated Second.php.\nUpdated first.md.\n", $result['stderr']);
        self::assertSame(
            "<!-- akashi-sync: canonical.php -->\n"
                . "```php\n<?php\n\necho 'current';\n```\n"
                . "<!-- akashi-sync-end -->\n",
            file_get_contents($this->workspace . '/first.md'),
        );
        self::assertSame(
            <<<'PHP'
<?php

/**
 * <!-- akashi-sync: canonical.php -->
 * ```php
 * <?php
 *
 * echo 'current';
 * ```
 * <!-- akashi-sync-end -->
 */
PHP,
            file_get_contents($this->workspace . '/Second.php'),
        );
    }

    public function testWriteIsSilentWhenEveryPresentationIsCurrent(): void
    {
        self::assertNotFalse(file_put_contents($this->workspace . '/canonical.php', "echo 'current';\n"));
        $contents = "<!-- akashi-sync: canonical.php -->\n"
            . "```php\necho 'current';\n```\n"
            . "<!-- akashi-sync-end -->\n";
        self::assertNotFalse(file_put_contents($this->workspace . '/example.md', $contents));

        $result = $this->runApplication([
            'sync',
            '--write',
            '--project-root=' . $this->workspace,
            $this->workspace . '/example.md',
        ]);

        self::assertSame(ExitCode::Success->value, $result['status']);
        self::assertSame('', $result['stdout']);
        self::assertSame('', $result['stderr']);
        self::assertSame($contents, file_get_contents($this->workspace . '/example.md'));
    }

    public function testWriteValidatesEveryDocumentBeforeChangingTheFirstOne(): void
    {
        self::assertNotFalse(file_put_contents($this->workspace . '/canonical.php', "echo 'current';\n"));
        $stale = "<!-- akashi-sync: canonical.php -->\n"
            . "```php\necho 'stale';\n```\n"
            . "<!-- akashi-sync-end -->\n";
        self::assertNotFalse(file_put_contents($this->workspace . '/first.md', $stale));
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/second.md',
            "<!-- akashi-sync: canonical.php -->\n```php\necho 'unterminated';\n```\n",
        ));

        $result = $this->runApplication([
            'sync',
            '--write',
            '--project-root=' . $this->workspace,
            $this->workspace . '/first.md',
            $this->workspace . '/second.md',
        ]);

        self::assertSame(ExitCode::CommandFailure->value, $result['status']);
        self::assertSame('', $result['stdout']);
        self::assertStringStartsWith('Synchronization failed:', $result['stderr']);
        self::assertSame($stale, file_get_contents($this->workspace . '/first.md'));
    }

    public function testWriteRejectsASelectedSymbolicLinkBeforeCanonicalization(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            self::markTestSkipped('Symbolic-link creation is not generally available to Windows test users.');
        }

        self::assertNotFalse(file_put_contents($this->workspace . '/canonical.php', "echo 'current';\n"));
        $stale = "<!-- akashi-sync: canonical.php -->\n"
            . "```php\necho 'stale';\n```\n"
            . "<!-- akashi-sync-end -->\n";
        self::assertNotFalse(file_put_contents($this->workspace . '/target.md', $stale));
        self::assertTrue(symlink($this->workspace . '/target.md', $this->workspace . '/alias.md'));

        $result = $this->runApplication([
            'sync',
            '--write',
            '--project-root=' . $this->workspace,
            $this->workspace . '/alias.md',
        ]);

        self::assertSame(ExitCode::CommandFailure->value, $result['status']);
        self::assertSame('', $result['stdout']);
        self::assertStringContainsString('write paths must not use symbolic links', $result['stderr']);
        self::assertSame($stale, file_get_contents($this->workspace . '/target.md'));
        self::assertTrue(is_link($this->workspace . '/alias.md'));
    }

    public function testWriteRejectsASelectedPathThroughASymbolicLinkDirectory(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            self::markTestSkipped('Symbolic-link creation is not generally available to Windows test users.');
        }

        self::assertTrue(mkdir($this->workspace . '/real', 0o700));
        self::assertNotFalse(file_put_contents($this->workspace . '/canonical.php', "echo 'current';\n"));
        $stale = "<!-- akashi-sync: canonical.php -->\n"
            . "```php\necho 'stale';\n```\n"
            . "<!-- akashi-sync-end -->\n";
        self::assertNotFalse(file_put_contents($this->workspace . '/real/example.md', $stale));
        self::assertTrue(symlink($this->workspace . '/real', $this->workspace . '/alias'));

        $result = $this->runApplication([
            'sync',
            '--write',
            '--project-root=' . $this->workspace,
            $this->workspace . '/alias/example.md',
        ]);

        self::assertSame(ExitCode::CommandFailure->value, $result['status']);
        self::assertSame('', $result['stdout']);
        self::assertStringContainsString('write paths must not use symbolic links', $result['stderr']);
        self::assertSame($stale, file_get_contents($this->workspace . '/real/example.md'));
    }

    public function testWriteRejectsAChangingSelectedWholeFileCanonicalDependency(): void
    {
        self::assertNotFalse(file_put_contents($this->workspace . '/canonical.php', "echo 'current';\n"));
        $source = "<?php\n\n"
            . "/**\n"
            . " * <!-- akashi-sync: canonical.php -->\n"
            . " * ```php\n"
            . " * echo 'stale';\n"
            . " * ```\n"
            . " * <!-- akashi-sync-end -->\n"
            . " */\n";
        self::assertNotFalse(file_put_contents($this->workspace . '/source.php', $source));
        $dependent = "<!-- akashi-sync: source.php -->\n"
            . "~~~php\n"
            . $source
            . "~~~\n"
            . "<!-- akashi-sync-end -->\n";
        self::assertNotFalse(file_put_contents($this->workspace . '/dependent.md', $dependent));

        $result = $this->runApplication([
            'sync',
            '--write',
            '--project-root=' . $this->workspace,
            $this->workspace . '/source.php',
            $this->workspace . '/dependent.md',
        ]);

        self::assertSame(ExitCode::CommandFailure->value, $result['status']);
        self::assertSame('', $result['stdout']);
        self::assertStringContainsString(
            'whole-file canonical source source.php is also being rewritten',
            $result['stderr'],
        );
        self::assertSame($source, file_get_contents($this->workspace . '/source.php'));
        self::assertSame($dependent, file_get_contents($this->workspace . '/dependent.md'));
    }

    public function testWriteAllowsASelectedNamedRegionDependencyUnaffectedByPhpDocRewriting(): void
    {
        self::assertNotFalse(file_put_contents($this->workspace . '/canonical.php', "echo 'current';\n"));
        $source = "<?php\n"
            . "// akashi-region: stable\n"
            . "echo 'stable';\n"
            . "// akashi-region-end: stable\n\n"
            . "/**\n"
            . " * <!-- akashi-sync: canonical.php -->\n"
            . " * ```php\n"
            . " * echo 'stale';\n"
            . " * ```\n"
            . " * <!-- akashi-sync-end -->\n"
            . " */\n";
        self::assertNotFalse(file_put_contents($this->workspace . '/source.php', $source));
        $dependent = "<!-- akashi-sync: source.php#stable -->\n"
            . "```php\n"
            . "echo 'stable';\n"
            . "```\n"
            . "<!-- akashi-sync-end -->\n";
        self::assertNotFalse(file_put_contents($this->workspace . '/dependent.md', $dependent));

        $result = $this->runApplication([
            'sync',
            '--write',
            '--project-root=' . $this->workspace,
            $this->workspace . '/source.php',
            $this->workspace . '/dependent.md',
        ]);

        self::assertSame(ExitCode::Success->value, $result['status']);
        self::assertSame('', $result['stdout']);
        self::assertSame("Updated source.php.\n", $result['stderr']);
        $rewrittenSource = file_get_contents($this->workspace . '/source.php');
        self::assertNotFalse($rewrittenSource);
        self::assertStringContainsString(" * echo 'current';\n", $rewrittenSource);
        self::assertSame($dependent, file_get_contents($this->workspace . '/dependent.md'));
    }

    public function testReportsEveryStaleSynchronizedPresentationWithItsCanonicalLocation(): void
    {
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/canonical.php',
            "<?php\n\necho 'current';\n",
        ));
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/first.md',
            "<!-- akashi-sync: canonical.php -->\n"
                . "```php\n<?php\n\necho 'stale';\n```\n"
                . "<!-- akashi-sync-end -->\n",
        ));
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/second.md',
            "<!-- akashi-sync: canonical.php -->\n"
                . "```php\n<?php\n\necho 'also stale';\n```\n"
                . "<!-- akashi-sync-end -->\n",
        ));

        $result = $this->runApplication([
            'sync',
            '--project-root=' . $this->workspace,
            $this->workspace . '/second.md',
            '--check',
            $this->workspace . '/first.md',
        ]);

        self::assertSame(ExitCode::CommandFailure->value, $result['status']);
        self::assertSame('', $result['stdout']);
        self::assertSame(
            "first.md:1: synchronized code differs from canonical.php (canonical code: canonical.php:1).\n"
                . "--- first.md:3 (presentation)\n"
                . "+++ canonical.php:1 (canonical)\n"
                . "@@ -1,3 +1,3 @@\n"
                . " <?php\n"
                . " \n"
                . "-echo 'stale';\n"
                . "+echo 'current';\n"
                . "second.md:1: synchronized code differs from canonical.php (canonical code: canonical.php:1).\n"
                . "--- second.md:3 (presentation)\n"
                . "+++ canonical.php:1 (canonical)\n"
                . "@@ -1,3 +1,3 @@\n"
                . " <?php\n"
                . " \n"
                . "-echo 'also stale';\n"
                . "+echo 'current';\n"
                . "2 synchronized presentations are stale.\n",
            $result['stderr'],
        );
    }

    public function testSynchronizationDiffBypassesConsoleMarkupAndAnsiFormatting(): void
    {
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/canonical.php',
            "<?php\n\necho '<info>current</info>';\n",
        ));
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/markup.md',
            "<!-- akashi-sync: canonical.php -->\n"
                . "```php\n<?php\n\necho '<error>stale</error>';\n```\n"
                . "<!-- akashi-sync-end -->\n",
        ));

        $result = $this->runApplication([
            '--ansi',
            'sync',
            '--check',
            '--project-root=' . $this->workspace,
            $this->workspace . '/markup.md',
        ]);

        self::assertSame(ExitCode::CommandFailure->value, $result['status']);
        self::assertSame('', $result['stdout']);
        self::assertStringContainsString("-echo '<error>stale</error>';", $result['stderr']);
        self::assertStringContainsString("+echo '<info>current</info>';", $result['stderr']);
        self::assertStringNotContainsString("\033", $result['stderr']);
    }

    public function testReportsMalformedSynchronizationAsACommandFailure(): void
    {
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/malformed.md',
            "<!-- akashi-sync: canonical.php -->\n```php\necho 'unterminated';\n```\n",
        ));

        $result = $this->runApplication([
            'sync',
            '--check',
            '--project-root=' . $this->workspace,
            $this->workspace . '/malformed.md',
        ]);

        self::assertSame(ExitCode::CommandFailure->value, $result['status']);
        self::assertSame('', $result['stdout']);
        self::assertStringStartsWith('Synchronization failed:', $result['stderr']);
        self::assertStringContainsString('has no following end directive', $result['stderr']);
    }

    public function testReportsAUnifiedDiffForAnEmptyPresentation(): void
    {
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/canonical.php',
            "echo 'canonical';\n",
        ));
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/empty.md',
            "<!-- akashi-sync: canonical.php -->\n```php\n```\n<!-- akashi-sync-end -->\n",
        ));

        $result = $this->runApplication([
            'sync',
            '--check',
            '--project-root=' . $this->workspace,
            $this->workspace . '/empty.md',
        ]);

        self::assertSame(ExitCode::CommandFailure->value, $result['status']);
        self::assertSame('', $result['stdout']);
        self::assertStringContainsString(
            "--- empty.md:3 (presentation)\n"
                . "+++ canonical.php:1 (canonical)\n"
                . "@@ -1,0 +1 @@\n"
                . "+echo 'canonical';\n",
            $result['stderr'],
        );
    }

    public function testUsesTheWorkingDirectoryAsTheDefaultRootAndReportsANamedCanonicalRegion(): void
    {
        self::assertTrue(mkdir($this->workspace . '/docs', 0o700));
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/canonical.php',
            "<?php\n"
                . "// akashi-region: selected\n"
                . "echo 'current';\n"
                . "// akashi-region-end: selected\n",
        ));
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/docs/example.md',
            "<!-- akashi-sync: canonical.php#selected -->\n"
                . "```php\necho 'stale';\n```\n"
                . "<!-- akashi-sync-end -->\n",
        ));
        $previousDirectory = getcwd();
        self::assertNotFalse($previousDirectory);
        self::assertTrue(chdir($this->workspace));

        try {
            $result = $this->runApplication(['sync', '--check', 'docs/example.md']);
        } finally {
            self::assertTrue(chdir($previousDirectory));
        }

        self::assertSame(ExitCode::CommandFailure->value, $result['status']);
        self::assertSame('', $result['stdout']);
        self::assertSame(
            "docs/example.md:1: synchronized code differs from canonical.php#selected "
                . "(canonical code: canonical.php:3).\n"
                . "--- docs/example.md:3 (presentation)\n"
                . "+++ canonical.php:3 (canonical)\n"
                . "@@ -1 +1 @@\n"
                . "-echo 'stale';\n"
                . "+echo 'current';\n"
                . "1 synchronized presentation is stale.\n",
            $result['stderr'],
        );
    }

    public function testRejectsAnUnsupportedSynchronizationFileWithoutIgnoringIt(): void
    {
        self::assertNotFalse(file_put_contents($this->workspace . '/example.md', "# No synchronized regions\n"));
        self::assertNotFalse(file_put_contents($this->workspace . '/ignored.txt', "not Markdown\n"));

        $result = $this->runApplication([
            'sync',
            '--check',
            '--project-root=' . $this->workspace,
            $this->workspace . '/example.md',
            $this->workspace . '/ignored.txt',
        ]);

        self::assertSame(ExitCode::CommandFailure->value, $result['status']);
        self::assertSame('', $result['stdout']);
        self::assertStringContainsString(
            'Synchronization file must use the case-sensitive .md or .php extension',
            $result['stderr'],
        );
    }

    public function testRejectsAWhitespaceOnlySynchronizationProjectRoot(): void
    {
        $result = $this->runApplication([
            'sync',
            '--check',
            '--project-root=  ',
            'example.md',
        ]);

        self::assertSame(ExitCode::CommandFailure->value, $result['status']);
        self::assertSame('', $result['stdout']);
        self::assertStringContainsString('Project root path must not be empty.', $result['stderr']);
    }

    public function testNormalizesBackslashesInASynchronizationFilePath(): void
    {
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/canonical.php',
            "<?php\n\necho 'current';\n",
        ));
        $file = $this->workspace . '/example.md';
        self::assertNotFalse(file_put_contents(
            $file,
            "<!-- akashi-sync: canonical.php -->\n"
                . "```php\n<?php\n\necho 'current';\n```\n"
                . "<!-- akashi-sync-end -->\n",
        ));

        $result = $this->runApplication([
            'sync',
            '--check',
            '--project-root=' . $this->workspace,
            str_replace('/', '\\', $file),
        ]);

        self::assertSame(ExitCode::Success->value, $result['status']);
        self::assertSame('', $result['stdout']);
        self::assertSame('', $result['stderr']);
    }

    public function testFormattingCheckIsSilentForCurrentInlineExamples(): void
    {
        self::assertTrue(mkdir($this->workspace . '/vendor/bin', 0o700, true));
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/vendor/bin/php-cs-fixer',
            "<?php\nexit(is_file(\$argv[count(\$argv) - 1]) ? 0 : 1);\n",
        ));
        $file = $this->workspace . '/examples.md';
        self::assertNotFalse(file_put_contents($file, "```php\n\$value = 1;\n```\n"));

        $result = $this->runApplication([
            'format',
            '--check',
            '--project-root=' . $this->workspace,
            $file,
        ]);

        self::assertSame(ExitCode::Success->value, $result['status']);
        self::assertSame('', $result['stdout']);
        self::assertSame('', $result['stderr']);
    }

    public function testFormattingCheckReportsDeterministicSourceAwareDiffs(): void
    {
        self::assertTrue(mkdir($this->workspace . '/tools'));
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/tools/fixer',
            <<<'PHP'
<?php
$path = $argv[count($argv) - 1];
$source = file_get_contents($path);
file_put_contents($path, str_replace('$value=1;', '$value = 1;', $source));
PHP,
        ));
        $file = $this->workspace . '/examples.md';
        self::assertNotFalse(file_put_contents($file, "```php\n\$value=1;\n```\n"));

        $result = $this->runApplication([
            'format',
            '--check',
            '--project-root=' . $this->workspace,
            '--php-cs-fixer=tools/fixer',
            $file,
        ]);

        self::assertSame(ExitCode::CommandFailure->value, $result['status']);
        self::assertSame('', $result['stdout']);
        self::assertStringStartsWith(
            'examples.md:2: inline example differs from PHP-CS-Fixer output.',
            $result['stderr'],
        );
        self::assertStringContainsString(
            "--- examples.md:2 (authored)\n"
                . "+++ examples.md:2 (formatted)\n"
                . "@@ -1 +1 @@\n"
                . "-\$value=1;\n"
                . "+\$value = 1;\n",
            $result['stderr'],
        );
        self::assertStringEndsWith("1 inline example requires formatting.\n", $result['stderr']);
    }

    public function testFormattingWriteUpdatesSeveralDocumentsInDeterministicPathOrder(): void
    {
        self::assertTrue(mkdir($this->workspace . '/tools'));
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/tools/fixer',
            <<<'PHP'
<?php
$path = $argv[count($argv) - 1];
$source = file_get_contents($path);
file_put_contents($path, str_replace(['$first=1;', '$second=2;'], ['$first = 1;', '$second = 2;'], $source));
PHP,
        ));
        $first = $this->workspace . '/z-first.md';
        self::assertNotFalse(file_put_contents($first, "```php\n\$first=1;\n```\n"));
        $second = $this->workspace . '/A-second.php';
        self::assertNotFalse(file_put_contents($second, <<<'PHP'
<?php
/**
 * ```php
 * $second=2;
 * ```
 */
PHP));

        $result = $this->runApplication([
            'format',
            '--project-root=' . $this->workspace,
            '--php-cs-fixer=tools/fixer',
            $first,
            '--write',
            $second,
        ]);

        self::assertSame(ExitCode::Success->value, $result['status']);
        self::assertSame('', $result['stdout']);
        self::assertSame("Updated A-second.php.\nUpdated z-first.md.\n", $result['stderr']);
        self::assertSame("```php\n\$first = 1;\n```\n", file_get_contents($first));
        self::assertSame(<<<'PHP'
<?php
/**
 * ```php
 * $second = 2;
 * ```
 */
PHP, file_get_contents($second));
    }

    public function testFormattingWriteIsSilentWhenEveryInlineExampleIsCurrent(): void
    {
        self::assertTrue(mkdir($this->workspace . '/vendor/bin', 0o700, true));
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/vendor/bin/php-cs-fixer',
            "<?php\nexit(is_file(\$argv[count(\$argv) - 1]) ? 0 : 1);\n",
        ));
        $file = $this->workspace . '/examples.md';
        $contents = "```php\n\$value = 1;\n```\n";
        self::assertNotFalse(file_put_contents($file, $contents));

        $result = $this->runApplication([
            'format',
            '--write',
            '--project-root=' . $this->workspace,
            $file,
        ]);

        self::assertSame(ExitCode::Success->value, $result['status']);
        self::assertSame('', $result['stdout']);
        self::assertSame('', $result['stderr']);
        self::assertSame($contents, file_get_contents($file));
    }

    public function testFormattingWriteValidatesEveryFormatterBeforeChangingTheFirstDocument(): void
    {
        self::assertTrue(mkdir($this->workspace . '/tools'));
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/tools/fixer',
            <<<'PHP'
<?php
$counter = dirname(__DIR__) . '/formatter-count';
$count = is_file($counter) ? (int) file_get_contents($counter) : 0;
file_put_contents($counter, (string) ++$count);
if ($count === 4) {
    fwrite(STDERR, 'second validation formatter failed');
    exit(8);
}
$path = $argv[count($argv) - 1];
$source = file_get_contents($path);
file_put_contents($path, str_replace('=1;', ' = 1;', $source));
PHP,
        ));
        $first = $this->workspace . '/first.md';
        $second = $this->workspace . '/second.md';
        $stale = "```php\n\$value=1;\n```\n";
        self::assertNotFalse(file_put_contents($first, $stale));
        self::assertNotFalse(file_put_contents($second, $stale));

        $result = $this->runApplication([
            'format',
            '--write',
            '--project-root=' . $this->workspace,
            '--php-cs-fixer=tools/fixer',
            $first,
            $second,
        ]);

        self::assertSame(ExitCode::CommandFailure->value, $result['status']);
        self::assertSame('', $result['stdout']);
        self::assertStringContainsString('second validation formatter failed', $result['stderr']);
        self::assertSame($stale, file_get_contents($first));
        self::assertSame($stale, file_get_contents($second));
    }

    public function testFormattingWriteRejectsAChangedCleanSelectedDocumentBeforeWriting(): void
    {
        self::assertTrue(mkdir($this->workspace . '/tools'));
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/tools/fixer',
            <<<'PHP'
<?php
$counter = dirname(__DIR__) . '/formatter-count';
$count = is_file($counter) ? (int) file_get_contents($counter) : 0;
file_put_contents($counter, (string) ++$count);
if ($count === 1) {
    $clean = dirname(__DIR__) . '/clean.md';
    $source = file_get_contents($clean);
    file_put_contents($clean, str_replace('Original prose.', 'Changed prose.', $source));
}
$path = $argv[count($argv) - 1];
$source = file_get_contents($path);
file_put_contents($path, str_replace('$stale=1;', '$stale = 1;', $source));
PHP,
        ));
        $clean = $this->workspace . '/clean.md';
        $originalClean = "Original prose.\n\n```php\n\$clean = 1;\n```\n";
        self::assertNotFalse(file_put_contents($clean, $originalClean));
        $stale = $this->workspace . '/stale.md';
        $originalStale = "```php\n\$stale=1;\n```\n";
        self::assertNotFalse(file_put_contents($stale, $originalStale));

        $result = $this->runApplication([
            'format',
            '--write',
            '--project-root=' . $this->workspace,
            '--php-cs-fixer=tools/fixer',
            $stale,
            $clean,
        ]);

        self::assertSame(ExitCode::CommandFailure->value, $result['status']);
        self::assertSame('', $result['stdout']);
        self::assertStringContainsString(
            'Formatting input changed during validation; refusing to write any files: clean.md.',
            $result['stderr'],
        );
        self::assertSame(
            str_replace('Original prose.', 'Changed prose.', $originalClean),
            file_get_contents($clean),
        );
        self::assertSame($originalStale, file_get_contents($stale));
    }

    public function testFormattingWriteRejectsAChangingFormatterResultBeforeWriting(): void
    {
        self::assertTrue(mkdir($this->workspace . '/tools'));
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/tools/fixer',
            <<<'PHP'
<?php
$counter = dirname(__DIR__) . '/formatter-count';
$count = is_file($counter) ? (int) file_get_contents($counter) : 0;
file_put_contents($counter, (string) ++$count);
$path = $argv[count($argv) - 1];
$source = file_get_contents($path);
$replacement = $count === 1 ? '$value = 1;' : '$value  = 1;';
file_put_contents($path, str_replace('$value=1;', $replacement, $source));
PHP,
        ));
        $file = $this->workspace . '/example.md';
        $stale = "```php\n\$value=1;\n```\n";
        self::assertNotFalse(file_put_contents($file, $stale));

        $result = $this->runApplication([
            'format',
            '--write',
            '--project-root=' . $this->workspace,
            '--php-cs-fixer=tools/fixer',
            $file,
        ]);

        self::assertSame(ExitCode::CommandFailure->value, $result['status']);
        self::assertSame('', $result['stdout']);
        self::assertStringContainsString('formatter result changed during validation', $result['stderr']);
        self::assertSame($stale, file_get_contents($file));
    }

    public function testFormattingWriteRejectsASelectedSymbolicLinkBeforeCanonicalization(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            self::markTestSkipped('Symbolic-link creation is not generally available to Windows test users.');
        }

        self::assertTrue(mkdir($this->workspace . '/tools'));
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/tools/fixer',
            <<<'PHP'
<?php
$path = $argv[count($argv) - 1];
$source = file_get_contents($path);
file_put_contents($path, str_replace('$value=1;', '$value = 1;', $source));
PHP,
        ));
        $target = $this->workspace . '/target.md';
        $stale = "```php\n\$value=1;\n```\n";
        self::assertNotFalse(file_put_contents($target, $stale));
        self::assertTrue(symlink($target, $this->workspace . '/alias.md'));

        $result = $this->runApplication([
            'format',
            '--write',
            '--project-root=' . $this->workspace,
            '--php-cs-fixer=tools/fixer',
            $this->workspace . '/alias.md',
        ]);

        self::assertSame(ExitCode::CommandFailure->value, $result['status']);
        self::assertSame('', $result['stdout']);
        self::assertStringContainsString('Formatting write paths must not use symbolic links', $result['stderr']);
        self::assertSame($stale, file_get_contents($target));
        self::assertTrue(is_link($this->workspace . '/alias.md'));
    }

    public function testFormattingWriteRejectsASelectedPathThroughASymbolicLinkDirectory(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            self::markTestSkipped('Symbolic-link creation is not generally available to Windows test users.');
        }

        self::assertTrue(mkdir($this->workspace . '/tools'));
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/tools/fixer',
            "<?php\nexit(0);\n",
        ));
        self::assertTrue(mkdir($this->workspace . '/real'));
        $target = $this->workspace . '/real/example.md';
        $contents = "```php\n\$value=1;\n```\n";
        self::assertNotFalse(file_put_contents($target, $contents));
        self::assertTrue(symlink($this->workspace . '/real', $this->workspace . '/alias'));

        $result = $this->runApplication([
            'format',
            '--write',
            '--project-root=' . $this->workspace,
            '--php-cs-fixer=tools/fixer',
            $this->workspace . '/alias/example.md',
        ]);

        self::assertSame(ExitCode::CommandFailure->value, $result['status']);
        self::assertSame('', $result['stdout']);
        self::assertStringContainsString('Formatting write paths must not use symbolic links', $result['stderr']);
        self::assertSame($contents, file_get_contents($target));
    }

    public function testFormattingWriteRejectsASelectedFileOutsideTheProjectBeforeRunningTheFormatter(): void
    {
        self::assertTrue(mkdir($this->workspace . '/tools'));
        self::assertNotFalse(file_put_contents(
            $this->workspace . '/tools/fixer',
            "<?php\nfile_put_contents(dirname(__DIR__) . '/formatter-ran', 'yes');\n",
        ));
        $outside = dirname($this->workspace) . '/outside-' . basename($this->workspace) . '.md';
        self::assertNotFalse(file_put_contents($outside, "```php\n\$value=1;\n```\n"));

        try {
            $result = $this->runApplication([
                'format',
                '--write',
                '--project-root=' . $this->workspace,
                '--php-cs-fixer=tools/fixer',
                $outside,
            ]);

            self::assertSame(ExitCode::CommandFailure->value, $result['status']);
            self::assertSame('', $result['stdout']);
            self::assertStringContainsString('is outside the project root', $result['stderr']);
            self::assertFileDoesNotExist($this->workspace . '/formatter-ran');
        } finally {
            @unlink($outside);
        }
    }

    public function testFormattingConfigurationFailuresUseTheCommandFailureStatus(): void
    {
        $file = $this->workspace . '/examples.md';
        self::assertNotFalse(file_put_contents($file, "```php\necho 1;\n```\n"));

        $result = $this->runApplication([
            'format',
            '--check',
            '--project-root=' . $this->workspace,
            $file,
        ]);

        self::assertSame(ExitCode::CommandFailure->value, $result['status']);
        self::assertSame('', $result['stdout']);
        self::assertStringStartsWith('Formatting failed:', $result['stderr']);
        self::assertStringContainsString('vendor/bin/php-cs-fixer', $result['stderr']);
    }

    public function testReportsUnexpectedFailuresWithTheSoftwareExitCode(): void
    {
        $output = new FailingConsoleOutput();
        $status = (new Application())->run(new ArgumentInput(['akashi']), $output);

        self::assertSame(ExitCode::SoftwareError->value, $status);
        self::assertStringContainsString(
            'RuntimeException: Unable to write output.',
            $output->fetchErrorOutput(),
        );
    }

    /**
     * @param list<string> $arguments
     *
     * @return array{status: int, stdout: string, stderr: string}
     */
    private function runApplication(array $arguments): array
    {
        $output = new BufferedConsoleOutput();
        $status = (new Application())->run(new ArgumentInput(['akashi', ...$arguments]), $output);

        return [
            'status' => $status,
            'stdout' => $output->fetch(),
            'stderr' => $output->fetchErrorOutput(),
        ];
    }
}

class BufferedConsoleOutput extends BufferedOutput implements ConsoleOutputInterface
{
    private OutputInterface $errorOutput;

    public function __construct()
    {
        parent::__construct(decorated: false);

        $this->errorOutput = new BufferedOutput(decorated: false);
    }

    public function getErrorOutput(): OutputInterface
    {
        return $this->errorOutput;
    }

    public function setErrorOutput(OutputInterface $error): void
    {
        $this->errorOutput = $error;
    }

    public function setVerbosity(int $level): void
    {
        parent::setVerbosity($level);
        $this->errorOutput->setVerbosity($level);
    }

    public function section(): ConsoleSectionOutput
    {
        throw new \LogicException('Console sections are not used by the CLI tests.');
    }

    public function fetchErrorOutput(): string
    {
        if (!$this->errorOutput instanceof BufferedOutput) {
            throw new \LogicException('The configured error output is not buffered.');
        }

        return $this->errorOutput->fetch();
    }
}

final class FailingConsoleOutput extends BufferedConsoleOutput
{
    protected function doWrite(string $message, bool $newline): void
    {
        throw new \RuntimeException('Unable to write output.');
    }
}
