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

use jbboehr\Akashi\Model\ProjectPath;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProjectPathTest extends TestCase
{
    #[DataProvider('normalizedPathProvider')]
    public function testNormalizesProjectRelativePaths(string $path, string $normalized): void
    {
        self::assertSame($normalized, (new ProjectPath($path))->value);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function normalizedPathProvider(): iterable
    {
        yield 'project root' => ['.', '.'];
        yield 'normalizes to project root' => ['docs/..', '.'];
        yield 'Windows separators' => ['docs\\guide.md', 'docs/guide.md'];
        yield 'empty and dot segments' => ['docs//./guide.md', 'docs/guide.md'];
        yield 'parent segment within root' => ['docs/drafts/../guide.md', 'docs/guide.md'];
    }

    #[DataProvider('invalidPathProvider')]
    public function testRejectsUnsafeOrEmptyPaths(string $path, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new ProjectPath($path);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidPathProvider(): iterable
    {
        yield 'empty' => ['', 'Project path must not be empty.'];
        yield 'whitespace' => ['   ', 'Project path must not be empty.'];
        yield 'NUL byte' => ["docs/guide\0.md", 'Project path must not contain NUL bytes.'];
        yield 'Unix absolute' => ['/docs/guide.md', 'Project path must be project-relative.'];
        yield 'Windows absolute' => ['C:\\docs\\guide.md', 'Project path must be project-relative.'];
        yield 'leading traversal' => ['../guide.md', 'Project path must not traverse outside the project root.'];
        yield 'nested traversal' => ['docs/../../guide.md', 'Project path must not traverse outside the project root.'];
    }
}
