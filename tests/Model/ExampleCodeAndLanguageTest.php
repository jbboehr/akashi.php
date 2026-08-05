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

use jbboehr\Akashi\Model\ExampleCode;
use jbboehr\Akashi\Model\Language;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ExampleCodeAndLanguageTest extends TestCase
{
    public function testPreservesExampleCodeExactly(): void
    {
        $source = "<?php\r\n\r\necho 1;\r\n";

        self::assertSame($source, (new ExampleCode($source))->source);
        self::assertSame('', (new ExampleCode(''))->source);
    }

    #[DataProvider('languageProvider')]
    public function testNormalizesLanguages(string $value, string $normalized): void
    {
        self::assertSame($normalized, (new Language($value))->value);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function languageProvider(): iterable
    {
        yield 'PHP' => [' PHP ', 'php'];
        yield 'version suffix' => ['PHP8', 'php8'];
        yield 'punctuation' => ['C++', 'c++'];
    }

    #[DataProvider('invalidLanguageProvider')]
    public function testRejectsInvalidLanguages(string $value): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Language must be a nonempty language identifier.');

        new Language($value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidLanguageProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'whitespace' => ['   '];
        yield 'starts with a number' => ['8php'];
        yield 'contains whitespace' => ['php script'];
        yield 'unsupported punctuation' => ['php.'];
    }
}
