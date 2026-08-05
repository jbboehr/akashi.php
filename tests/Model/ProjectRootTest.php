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

use jbboehr\Akashi\Model\ProjectRoot;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProjectRootTest extends TestCase
{
    #[DataProvider('normalizedRootProvider')]
    public function testNormalizesAbsoluteProjectRoots(string $root, string $normalized): void
    {
        self::assertSame($normalized, (new ProjectRoot($root))->value);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function normalizedRootProvider(): iterable
    {
        yield 'Unix path' => ['/var/project/', '/var/project'];
        yield 'Unix filesystem root' => ['/', '/'];
        yield 'Windows path' => ['C:\\project\\', 'C:/project'];
        yield 'Windows drive root' => ['C:\\', 'C:/'];
        yield 'UNC path' => ['\\\\server\\project\\', '//server/project'];
    }

    #[DataProvider('invalidRootProvider')]
    public function testRejectsInvalidProjectRoots(string $root, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new ProjectRoot($root);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidRootProvider(): iterable
    {
        yield 'empty' => ['', 'Project root must not be empty.'];
        yield 'whitespace' => ['   ', 'Project root must not be empty.'];
        yield 'NUL byte' => ["/var/project\0name", 'Project root must not contain NUL bytes.'];
        yield 'relative path' => ['project', 'Project root must be an absolute path.'];
        yield 'Windows drive-relative path' => ['C:project', 'Project root must be an absolute path.'];
    }
}
