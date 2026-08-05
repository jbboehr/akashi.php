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

use jbboehr\Akashi\Document;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DocumentTest extends TestCase
{
    public function testPreservesPathAndContentsExactly(): void
    {
        $contents = "First line\r\nSecond line\r\n";
        $document = new Document('docs/guide.md', $contents);

        self::assertSame('docs/guide.md', $document->path);
        self::assertSame($contents, $document->contents);
    }

    #[DataProvider('invalidPathProvider')]
    public function testRejectsInvalidPaths(string $path, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new Document($path, '');
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidPathProvider(): iterable
    {
        yield 'empty' => ['', 'Document path must not be empty.'];
        yield 'whitespace' => ['   ', 'Document path must not be empty.'];
        yield 'NUL byte' => ["docs/guide\0.md", 'Document path must not contain NUL bytes.'];
    }
}
