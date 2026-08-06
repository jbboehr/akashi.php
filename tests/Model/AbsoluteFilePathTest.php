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

namespace jbboehr\Akashi\Tests\Model;

use jbboehr\Akashi\Model\AbsoluteFilePath;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AbsoluteFilePathTest extends TestCase
{
    public function testNormalizesSeparatorsAndATrailingSeparator(): void
    {
        self::assertSame('/project/vendor/autoload.php', (new AbsoluteFilePath('/project/vendor/autoload.php/'))->value);
        self::assertSame('C:/project/bootstrap.php', (new AbsoluteFilePath('C:\\project\\bootstrap.php'))->value);
    }

    #[DataProvider('invalidPathProvider')]
    public function testRejectsInvalidPaths(string $path, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new AbsoluteFilePath($path);
    }

    /** @return iterable<string, array{string, string}> */
    public static function invalidPathProvider(): iterable
    {
        yield 'empty' => ['', 'must not be empty'];
        yield 'whitespace' => [" \n", 'must not be empty'];
        yield 'NUL byte' => ["/project/boot\0strap.php", 'must not contain NUL bytes'];
        yield 'relative' => ['vendor/autoload.php', 'must be absolute'];
        yield 'Unix root' => ['/', 'must identify a file'];
        yield 'double Unix root separators' => ['//', 'must identify a file'];
        yield 'triple Unix root separators' => ['///', 'must identify a file'];
        yield 'Windows root' => ['C:\\', 'must identify a file'];
        yield 'repeated Windows root separators' => ['C://', 'must identify a file'];
    }
}
