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

namespace jbboehr\Akashi\Tests\Execution;

use jbboehr\Akashi\Execution\Exception\RuntimeConfigurationException;
use jbboehr\Akashi\Execution\ExecutionMode;
use jbboehr\Akashi\Execution\RuntimeConfiguration;
use jbboehr\Akashi\Model\ProjectPath;
use jbboehr\Akashi\Model\ProjectRoot;
use PHPUnit\Framework\TestCase;

final class RuntimeConfigurationTest extends TestCase
{
    private string $workspace;
    private string $projectRoot;

    protected function setUp(): void
    {
        $workspace = tempnam(sys_get_temp_dir(), 'akashi-runtime-config-');
        self::assertNotFalse($workspace);
        self::assertTrue(unlink($workspace));
        self::assertTrue(mkdir($workspace . '/project/vendor', 0o700, true));
        self::assertNotFalse(file_put_contents($workspace . '/project/vendor/autoload.php', '<?php'));
        self::assertNotFalse(file_put_contents($workspace . '/outside.php', '<?php'));

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

    public function testCanonicalizesTheProjectRootAndDefaultsToInProcess(): void
    {
        $configuration = RuntimeConfiguration::forProject($this->projectRoot . '/vendor/..');

        self::assertSame($this->projectRoot, $configuration->projectRoot->value);
        self::assertNull($configuration->bootstrap);
        self::assertSame(ExecutionMode::InProcess, $configuration->defaultExecutionMode);
    }

    public function testAcceptsTypedPathsWithoutMutatingEarlierConfigurations(): void
    {
        $base = RuntimeConfiguration::forProject(new ProjectRoot($this->projectRoot));
        $bootstrapped = $base->withBootstrap(new ProjectPath('vendor/autoload.php'));
        $separate = $bootstrapped->withDefaultExecutionMode(ExecutionMode::SeparateProcess);

        self::assertNull($base->bootstrap);
        self::assertSame($this->projectRoot . '/vendor/autoload.php', $bootstrapped->bootstrap?->value);
        self::assertSame(ExecutionMode::InProcess, $bootstrapped->defaultExecutionMode);
        self::assertSame($bootstrapped->bootstrap, $separate->bootstrap);
        self::assertSame(ExecutionMode::SeparateProcess, $separate->defaultExecutionMode);
    }

    public function testRejectsAMissingProjectRoot(): void
    {
        $missing = $this->workspace . '/missing';

        $this->expectException(RuntimeConfigurationException::class);
        $this->expectExceptionMessage('Runtime project root does not exist or is not a directory: ' . $missing);

        RuntimeConfiguration::forProject($missing);
    }

    public function testRejectsAMissingBootstrap(): void
    {
        $configuration = RuntimeConfiguration::forProject($this->projectRoot);

        $this->expectException(RuntimeConfigurationException::class);
        $this->expectExceptionMessage('Runtime bootstrap does not exist or is not a readable file: missing.php');

        $configuration->withBootstrap('missing.php');
    }

    public function testRejectsABootstrapDirectory(): void
    {
        $configuration = RuntimeConfiguration::forProject($this->projectRoot);

        $this->expectException(RuntimeConfigurationException::class);
        $this->expectExceptionMessage('Runtime bootstrap does not exist or is not a readable file: vendor');

        $configuration->withBootstrap('vendor');
    }

    public function testRejectsABootstrapSymlinkThatResolvesOutsideTheProject(): void
    {
        self::assertTrue(symlink($this->workspace . '/outside.php', $this->projectRoot . '/external-bootstrap.php'));
        $configuration = RuntimeConfiguration::forProject($this->projectRoot);

        $this->expectException(RuntimeConfigurationException::class);
        $this->expectExceptionMessage(
            'Runtime bootstrap must resolve within the project root: external-bootstrap.php',
        );

        $configuration->withBootstrap('external-bootstrap.php');
    }
}
