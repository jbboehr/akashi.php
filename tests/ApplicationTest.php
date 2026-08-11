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
use jbboehr\Akashi\Cli\ExitCode;
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
            'akashi extract --marker-name=NAME FILE MARKER-ID',
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
        yield 'unknown extract option' => [
            ['extract', '--marker-name=selected-example', '--unknown', 'examples.md', 'chosen'],
            'Unknown extract option: --unknown.',
        ];
        yield 'missing positional argument' => [
            ['extract', '--marker-name=selected-example', 'examples.md'],
            'The extract command requires exactly one documentation file and marker ID.',
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

        self::assertSame(ExitCode::ExtractionFailure->value, $result['status']);
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

        self::assertSame(ExitCode::ExtractionFailure->value, $result['status']);
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

        self::assertSame(ExitCode::ExtractionFailure->value, $result['status']);
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

        self::assertSame(ExitCode::ExtractionFailure->value, $result['status']);
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
}
