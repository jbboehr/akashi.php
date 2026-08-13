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

use jbboehr\Akashi\Formatting\Exception\FormattingConfigurationException;
use jbboehr\Akashi\Formatting\PhpCsFixerConfiguration;
use PHPUnit\Framework\TestCase;

final class PhpCsFixerConfigurationTest extends TestCase
{
    private string $workspace;

    protected function setUp(): void
    {
        $workspace = tempnam(sys_get_temp_dir(), 'akashi-format-config-');
        self::assertNotFalse($workspace);
        self::assertTrue(unlink($workspace));
        self::assertTrue(mkdir($workspace, 0o700));
        self::assertTrue(mkdir($workspace . '/vendor/bin', 0o700, true));
        self::assertNotFalse(file_put_contents($workspace . '/vendor/bin/php-cs-fixer', "<?php\n"));
        self::assertNotFalse(file_put_contents($workspace . '/fixer.php', "<?php\nreturn [];\n"));
        $this->workspace = $workspace;
    }

    protected function tearDown(): void
    {
        if (is_link($this->workspace . '/outside')) {
            self::assertTrue(unlink($this->workspace . '/outside'));
        }
        foreach (['fixer.php', 'vendor/bin/php-cs-fixer'] as $path) {
            if (is_file($this->workspace . '/' . $path)) {
                self::assertTrue(unlink($this->workspace . '/' . $path));
            }
        }
        if (is_dir($this->workspace . '/vendor/bin')) {
            self::assertTrue(rmdir($this->workspace . '/vendor/bin'));
            self::assertTrue(rmdir($this->workspace . '/vendor'));
        }
        if (is_dir($this->workspace)) {
            self::assertTrue(rmdir($this->workspace));
        }
    }

    public function testCanonicalizesTheProjectExecutableAndOptionalConfiguration(): void
    {
        $configuration = PhpCsFixerConfiguration::forProject(
            $this->workspace,
            'vendor/bin/php-cs-fixer',
            'fixer.php',
        );

        self::assertSame(str_replace('\\', '/', $this->workspace), $configuration->projectRoot->value);
        self::assertSame(
            str_replace('\\', '/', $this->workspace . '/vendor/bin/php-cs-fixer'),
            $configuration->executable->value,
        );
        self::assertSame(str_replace('\\', '/', $this->workspace . '/fixer.php'), $configuration->config?->value);
    }

    public function testRejectsAMissingExecutable(): void
    {
        $this->expectException(FormattingConfigurationException::class);
        $this->expectExceptionMessage('PHP-CS-Fixer executable does not exist or is not a readable file: missing.');

        PhpCsFixerConfiguration::forProject($this->workspace, 'missing');
    }

    public function testRejectsAConfigurationResolvingOutsideTheProject(): void
    {
        $outside = tempnam(sys_get_temp_dir(), 'akashi-format-outside-');
        self::assertNotFalse($outside);
        self::assertTrue(symlink($outside, $this->workspace . '/outside'));

        try {
            $this->expectException(FormattingConfigurationException::class);
            $this->expectExceptionMessage('PHP-CS-Fixer configuration must resolve within the project root: outside.');

            PhpCsFixerConfiguration::forProject($this->workspace, 'vendor/bin/php-cs-fixer', 'outside');
        } finally {
            self::assertTrue(unlink($outside));
        }
    }
}
