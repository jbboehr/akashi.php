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

namespace jbboehr\Akashi\Tests\Conformance;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class CliConformanceTest extends TestCase
{
    public function testHelpAndVersionUseSuccessfulStandardOutput(): void
    {
        $help = self::executeCli('--help');
        self::assertSame(0, $help->getExitCode(), $help->getErrorOutput());
        self::assertStringStartsWith('Akashi ', $help->getOutput());
        self::assertStringContainsString('Available commands:', $help->getOutput());
        self::assertSame('', $help->getErrorOutput());

        $version = self::executeCli('--version');
        self::assertSame(0, $version->getExitCode(), $version->getErrorOutput());
        self::assertMatchesRegularExpression('/\AAkashi .+\n\z/', $version->getOutput());
        self::assertSame('', $version->getErrorOutput());
    }

    public function testUsageAndExtractionFailuresUseStableStatusesAndStandardError(): void
    {
        $usage = self::executeCli('unknown');
        self::assertSame(2, $usage->getExitCode());
        self::assertSame('', $usage->getOutput());
        self::assertStringStartsWith('Usage error:', $usage->getErrorOutput());

        $fixture = self::projectRoot() . '/tests/Fixtures/Conformance/cli.md';
        $extraction = self::executeCli(
            'extract',
            $fixture,
            'missing',
        );
        self::assertSame(1, $extraction->getExitCode());
        self::assertSame('', $extraction->getOutput());
        self::assertStringStartsWith('Extraction failed:', $extraction->getErrorOutput());
    }

    public function testSuccessfulExtractionUsesStableOutputAndStatus(): void
    {
        $fixture = self::projectRoot() . '/tests/Fixtures/Conformance/cli.md';
        $extraction = self::executeCli(
            'extract',
            $fixture,
            'selected',
        );

        self::assertSame(0, $extraction->getExitCode(), $extraction->getErrorOutput());
        self::assertSame("<?php\n\necho 'selected';\n", $extraction->getOutput());
        self::assertSame('', $extraction->getErrorOutput());
    }

    public function testSynchronizationChecksUseStableOutputAndStatuses(): void
    {
        $projectRoot = self::projectRoot();
        $current = self::executeCli(
            'sync',
            '--check',
            '--project-root=' . $projectRoot,
            $projectRoot . '/tests/Fixtures/Conformance/sync.md',
        );

        self::assertSame(0, $current->getExitCode(), $current->getErrorOutput());
        self::assertSame('', $current->getOutput());
        self::assertSame('', $current->getErrorOutput());

        $stale = self::executeCli(
            'sync',
            '--check',
            '--project-root=' . $projectRoot,
            $projectRoot . '/tests/Fixtures/Conformance/sync-stale.md',
        );

        self::assertSame(1, $stale->getExitCode());
        self::assertSame('', $stale->getOutput());
        self::assertStringStartsWith(
            'tests/Fixtures/Conformance/sync-stale.md:1: synchronized code differs',
            $stale->getErrorOutput(),
        );
        self::assertStringContainsString(
            "--- tests/Fixtures/Conformance/sync-stale.md:4 (presentation)\n"
                . "+++ tests/Fixtures/Conformance/sync.php:38 (canonical)\n"
                . "@@ -1 +1 @@\n"
                . "-echo 'stale';\n"
                . "+echo 'synchronized';\n",
            $stale->getErrorOutput(),
        );
        self::assertStringEndsWith("1 synchronized presentation is stale.\n", $stale->getErrorOutput());
    }

    public function testFormattingChecksUseStableOutputAndStatuses(): void
    {
        $projectRoot = self::projectRoot();
        $options = [
            'format',
            '--check',
            '--project-root=' . $projectRoot,
            '--config=.php-cs-fixer.dist.php',
        ];
        $current = self::executeCli(...[
            ...$options,
            $projectRoot . '/tests/Fixtures/Conformance/format.md',
        ]);

        self::assertSame(0, $current->getExitCode(), $current->getErrorOutput());
        self::assertSame('', $current->getOutput());
        self::assertSame('', $current->getErrorOutput());

        $stale = self::executeCli(...[
            ...$options,
            $projectRoot . '/tests/Fixtures/Conformance/format-stale.md',
        ]);

        self::assertSame(1, $stale->getExitCode());
        self::assertSame('', $stale->getOutput());
        self::assertStringStartsWith(
            'tests/Fixtures/Conformance/format-stale.md:4: inline example differs',
            $stale->getErrorOutput(),
        );
        self::assertStringContainsString(
            "--- tests/Fixtures/Conformance/format-stale.md:4 (authored)\n"
                . "+++ tests/Fixtures/Conformance/format-stale.md:4 (formatted)\n"
                . "@@ -1 +1 @@\n"
                . "-\$value=1;\n"
                . "+\$value = 1;\n",
            $stale->getErrorOutput(),
        );
        self::assertStringEndsWith("1 inline example requires formatting.\n", $stale->getErrorOutput());
    }

    private static function executeCli(string ...$arguments): Process
    {
        $projectRoot = self::projectRoot();
        $process = new Process([PHP_BINARY, $projectRoot . '/bin/akashi', ...$arguments], $projectRoot);
        $process->run();

        return $process;
    }

    private static function projectRoot(): string
    {
        return dirname(__DIR__, 2);
    }
}
