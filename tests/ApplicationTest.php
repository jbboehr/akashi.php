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
use jbboehr\Akashi\Cli\Command;
use jbboehr\Akashi\Cli\ExitCode;
use jbboehr\Akashi\Cli\ExtractCommand;
use jbboehr\Akashi\Cli\SyncCheckCommand;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

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
        self::assertStringStartsWith("Akashi — executable documentation testing for PHP.\n", $result['stdout']);
        self::assertStringContainsString(
            'akashi extract --marker-name=NAME [--project-root=PATH] FILE MARKER-ID',
            $result['stdout'],
        );
        self::assertStringContainsString(
            'akashi sync --check [--project-root=PATH] FILE [FILE ...]',
            $result['stdout'],
        );
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
        self::assertSame('Akashi ' . $version . "\n", $result['stdout']);
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
            '--marker-name=selected-example',
            $file,
            'chosen',
        ]);

        self::assertSame(ExitCode::Success->value, $result['status']);
        self::assertSame("<?php\r\n\r\necho 'selected';\n", $result['stdout']);
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
            '--marker-name=selected-example',
            $file,
            'chosen',
        ]);

        self::assertSame(ExitCode::Success->value, $result['status']);
        self::assertSame("echo 'phpdoc';\n", $result['stdout']);
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
            '--marker-name=selected-example',
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
        self::assertStringContainsString('Usage:', $result['stderr']);
    }

    /**
     * @return iterable<string, array{list<string>, string}>
     */
    public static function usageFailureProvider(): iterable
    {
        yield 'unknown command' => [['unknown'], 'Unknown command: unknown.'];
        yield 'missing marker name' => [
            ['extract', 'examples.md', 'chosen'],
            'The extract command requires --marker-name=NAME.',
        ];
        yield 'duplicate marker name' => [
            ['extract', '--marker-name=first', '--marker-name=second', 'examples.md', 'chosen'],
            'The --marker-name option may be specified only once.',
        ];
        yield 'duplicate project root' => [
            [
                'extract',
                '--marker-name=selected-example',
                '--project-root=first',
                '--project-root=second',
                'examples.md',
                'chosen',
            ],
            'The --project-root option may be specified only once.',
        ];
        yield 'unknown extract option' => [
            ['extract', '--marker-name=selected-example', '--unknown', 'examples.md', 'chosen'],
            'Unknown extract option: --unknown.',
        ];
        yield 'missing positional argument' => [
            ['extract', '--marker-name=selected-example', 'examples.md'],
            'The extract command requires exactly one documentation file and marker ID.',
        ];
        yield 'missing sync check mode' => [
            ['sync', 'README.md'],
            'The sync command currently requires --check.',
        ];
        yield 'duplicate sync check mode' => [
            ['sync', '--check', '--check', 'README.md'],
            'The --check option may be specified only once.',
        ];
        yield 'missing sync file' => [
            ['sync', '--check'],
            'The sync command requires at least one Markdown or PHP file.',
        ];
        yield 'unknown sync option' => [
            ['sync', '--check', '--write', 'README.md'],
            'Unknown sync option: --write.',
        ];
        yield 'duplicate sync project root' => [
            ['sync', '--check', '--project-root=first', '--project-root=second', 'README.md'],
            'The --project-root option may be specified only once.',
        ];
    }

    public function testReportsExtractionFailuresWithoutWritingToStandardOutput(): void
    {
        $file = $this->workspace . '/examples.md';
        self::assertNotFalse(file_put_contents($file, "```php\necho 1;\n```\n"));

        $result = $this->runApplication([
            'extract',
            '--marker-name=selected-example',
            $file,
            'missing',
        ]);

        self::assertSame(ExitCode::CommandFailure->value, $result['status']);
        self::assertSame('', $result['stdout']);
        self::assertStringContainsString(
            'Marker ID missing was not found in the example corpus.',
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
            '--marker-name=selected-example',
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
            'Unknown Akashi directive "unknown"',
        ];
        yield 'duplicate marker' => [
            "<!-- selected-example: chosen -->\n```php\necho 1;\n```\n\n"
                . "<!-- selected-example: chosen -->\n```php\necho 2;\n```\n",
            'Duplicate marker ID chosen',
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

    public function testValidatesTheMarkerIdBeforeReadingTheFile(): void
    {
        $result = $this->runApplication([
            'extract',
            '--marker-name=selected-example',
            $this->workspace . '/missing.md',
            'Invalid ID',
        ]);

        self::assertSame(ExitCode::CommandFailure->value, $result['status']);
        self::assertSame('', $result['stdout']);
        self::assertStringContainsString('Marker ID must use lowercase kebab-case.', $result['stderr']);
        self::assertStringNotContainsString('does not exist', $result['stderr']);
    }

    public function testRejectsAWhitespaceOnlyFilePath(): void
    {
        $result = $this->runApplication([
            'extract',
            '--marker-name=selected-example',
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
                '--marker-name=selected-example',
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
            '--marker-name=selected-example',
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
            '--marker-name=selected-example',
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
        self::assertStringStartsWith('Synchronization check failed:', $result['stderr']);
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

    public function testCommandsHonorTheInterfaceNamedOutputArgument(): void
    {
        $extractFile = $this->workspace . '/extract.md';
        self::assertNotFalse(file_put_contents(
            $extractFile,
            "<!-- selected-example: chosen -->\n```php\necho 'selected';\n```\n",
        ));
        $canonicalFile = $this->workspace . '/canonical.php';
        self::assertNotFalse(file_put_contents($canonicalFile, "echo 'current';\n"));
        $syncFile = $this->workspace . '/sync.md';
        self::assertNotFalse(file_put_contents(
            $syncFile,
            "<!-- akashi-sync: canonical.php -->\n"
                . "```php\necho 'stale';\n```\n"
                . "<!-- akashi-sync-end -->\n",
        ));

        $extraction = $this->executeCommandWithNamedArguments(
            new ExtractCommand(),
            ['--marker-name=selected-example', $extractFile, 'chosen'],
        );
        $synchronization = $this->executeCommandWithNamedArguments(
            new SyncCheckCommand(),
            ['--check', '--project-root=' . $this->workspace, $syncFile],
        );

        self::assertSame(ExitCode::Success, $extraction['status']);
        self::assertSame("echo 'selected';\n", $extraction['output']);
        self::assertSame(ExitCode::CommandFailure, $synchronization['status']);
        self::assertStringStartsWith('sync.md:1: synchronized code differs', $synchronization['output']);
    }

    public function testReportsUnexpectedFailuresWithTheSoftwareExitCode(): void
    {
        $stderr = '';

        $status = Application::run(
            [],
            static function (string $message): never {
                throw new \RuntimeException('Unable to write output.');
            },
            static function (string $message) use (&$stderr): void {
                $stderr .= $message;
            },
        );

        self::assertSame(ExitCode::SoftwareError->value, $status);
        self::assertStringContainsString('RuntimeException: Unable to write output.', $stderr);
    }

    /**
     * @param list<string> $arguments
     *
     * @return array{status: int, stdout: string, stderr: string}
     */
    private function runApplication(array $arguments): array
    {
        $stdout = '';
        $stderr = '';
        $status = Application::run(
            $arguments,
            static function (string $message) use (&$stdout): void {
                $stdout .= $message;
            },
            static function (string $message) use (&$stderr): void {
                $stderr .= $message;
            },
        );

        return ['status' => $status, 'stdout' => $stdout, 'stderr' => $stderr];
    }

    /**
     * @param list<string> $arguments
     *
     * @return array{status: ExitCode, output: string}
     */
    private function executeCommandWithNamedArguments(Command $command, array $arguments): array
    {
        $output = '';
        $status = $command->execute(
            arguments: $arguments,
            output: static function (string $message) use (&$output): void {
                $output .= $message;
            },
        );

        return ['status' => $status, 'output' => $output];
    }
}
